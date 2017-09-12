<?php

namespace Drupal\domain_source\HttpKernel;

use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the outbound path using path alias lookups.
 */
class DomainSourcePathProcessor implements OutboundPathProcessorInterface {

  /**
   * The Domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface $loader
   */
  protected $loader;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainSourcePathProcessor object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    static $active_domain;

    if (!isset($active_domain)) {
      // Ensure that the loader has run.
      // In some tests, the kernel event has not.
      $active = \Drupal::service('domain.negotiator')->getActiveDomain();
      if (empty($active)) {
        $active = \Drupal::service('domain.negotiator')->getActiveDomain(TRUE);
      }
      $active_domain = $active;
    }

    // Only act on valid internal paths and when a domain loads.
    if (empty($active_domain) || empty($path) || !empty($options['external'])) {
      return $path;
    }

    // Set the default source information.
    $source = NULL;
    $options['active_domain'] = $active_domain;

    // Get the current language.
    $langcode = NULL;
    if (!empty($options['language'])) {
      $langcode = $options['language']->getId();
    }
    // Get the URL object for this request.
    $alias = \Drupal::service('path.alias_manager')->getPathByAlias($path, $langcode);
    $url = Url::fromUserInput($alias, $options);

    // Load the entity to check
    if (!empty($options['entity'])) {
      $entity = $options['entity'];
    }
    else {
      $parameters = $url->getRouteParameters();
      if (!empty($parameters)) {
        try {
          $entity_type = key($parameters);
          $repository = \Drupal::service('entity_type.repository');
          $types = $repository->getEntityTypeLabels('content');
          $entity_types = array_flip(array_keys($types['Content']));
          $manager = \Drupal::entityTypeManager();
          if (!empty($entity_type) && isset($entity_types[$entity_type])) {
            $storage = $manager->getDefinition($entity_type);
            $entity = $manager->getStorage($entity_type)->load($parameters[$entity_type]);
          }
        }
        catch(Exception $e) {
          // @TODO error capture.
        }
      }
    }
    // One hook for entities.
    if (!empty($entity) && $this->allowedRoute($url->getRouteName())) {
      if (!empty($langcode) && $entity->hasTranslation($langcode) && $translation = $entity->getTranslation($langcode)) {
        $entity = $translation;
      }
      if ($target_id = domain_source_get($entity)) {
        $source = $this->loader->load($target_id);
      }
      $this->moduleHandler->alter('domain_source', $source, $path, $options);
    }
    // One for other, because the latter is resource-intensive.
    else {
      $this->moduleHandler->alter('domain_source_path', $source, $path, $options);
    }
    // If a source domain is specified, rewrite the link.
    if (!empty($source)) {
      // Note that url rewrites add a leading /, which getPath() also adds.
      $options['base_url'] = trim($source->getPath(), '/');
      $options['absolute'] = TRUE;
    }
    return $path;
  }

  /**
   * Derive entity data from a given path.
   *
   * @param $path
   *   The drupal path, e.g. /node/2.
   * @param $options array
   *   The options passed to the path processor.
   * @param $type
   *   The entity type to check.
   *
   * @return $entity|NULL
   */
  public static function getEntity($path, $options, $type = 'node', $langcode = NULL) {
    $entity = NULL;
    if (isset($options['entity_type']) && $options['entity_type'] == $type) {
      $entity = $options['entity'];
    }
    elseif (isset($options['route'])) {
      // Derive the route pattern and check that it maps to the expected entity
      // type.
      $route_path = $options['route']->getPath();
      $entityManager = \Drupal::entityTypeManager();
      $entityType = $entityManager->getDefinition($type);
      $links = $entityType->getLinkTemplates();

      // Check that the route pattern is an entity template.
      if (in_array($route_path, $links)) {
        $parts = explode('/', $route_path);
        $i = 0;
        foreach ($parts as $part) {
          if (!empty($part)) {
            $i++;
          }
          if ($part == '{' . $type . '}') {
            break;
          }
        }
        // Get Node path if alias.
        $node_path = \Drupal::service('path.alias_manager')->getPathByAlias($path, $langcode);
        // Look! We're using arg() in Drupal 8 because we have to.
        $args = explode('/', $node_path);
        if (isset($args[$i]) && Url::fromUserInput($node_path)->getRouteName() == 'entity.node.canonical') {
          $entity = \Drupal::entityTypeManager()->getStorage($type)->load($args[$i]);
        }
      }
    }
    // For translatable entities, make sure we are loading the proper translation.
    // This seems to be unreliable if we let core $entity->get(FIELD_NAME) handle it.
    if (!empty($entity) && $entity->isTranslatable()) {
      $langcode = NULL;
      if (!empty($options['language'])) {
        $langcode = $options['language']->getId();
      }
      else {
        $language = \Drupal::languageManager()->getCurrentLanguage();
        if ($language->getId() != LanguageInterface::LANGCODE_DEFAULT) {
          $langcode = $language->getId();
        }
      }
      if (!empty($langcode) && $entity->hasTranslation($langcode) && $translation = $entity->getTranslation($langcode)) {
        return $translation;
      }
    }
    return $entity;
  }

  public function allowedRoute($name) {
    return TRUE;
  }

}

