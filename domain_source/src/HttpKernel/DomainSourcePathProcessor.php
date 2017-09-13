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

    // Check the route, if available. Entities can be configured to
    // only rewrite specific routes.
    if ($this->allowedRoute($url->getRouteName())) {
      // Load the entity to check
      if (!empty($options['entity'])) {
        $entity = $options['entity'];
      }
      else {
        // @TODO: Move this to the getEntity() method.
        $parameters = $url->getRouteParameters();
        if (!empty($parameters)) {
          $entity = $this->getEntity($parameters);
        }
      }
    }
    // One hook for entities.
    if (!empty($entity)) {
      // Enmsure we send the right translation.
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
   * Derive entity data from a given route's parameters.
   *
   * @param $parameters
   *   An array of route parameters.
   *
   * @return $entity|NULL
   */
  public static function getEntity($parameters) {
    $entity = NULL;
    try {
      $entity_type = key($parameters);
      // @TODO: Load statically / inject.
      $repository = \Drupal::service('entity_type.repository');
      $types = $repository->getEntityTypeLabels('content');
      $entity_types = array_flip(array_keys($types['Content']));
      // @TODO: Load statically / inject.
      $manager = \Drupal::entityTypeManager();
      if (!empty($entity_type) && isset($entity_types[$entity_type])) {
        $storage = $manager->getDefinition($entity_type);
        $entity = $manager->getStorage($entity_type)->load($parameters[$entity_type]);
      }
    }
    catch(Exception $e) {
      // @TODO error capture.
    }
    return $entity;
  }

  /**
   * Checks that a route's common name is not disallowed.
   *
   * Looks at the name (e.g. canonical) of the route without regard for
   * the entity type.
   *
   * @parameter $name
   *   The route name being checked.
   *
   * @return boolean
   */
  public function allowedRoute($name) {
    // @TODO: Load statically / inject.
    $config = \Drupal::config('domain_source.settings');
    $exclude = array_flip($config->get('exclude_routes', []));
    $parts = explode('.', $name);
    $route_name = end($parts);
    // Config is stored as an array. Empty items are not excluded.
    return !isset($exclude[$route_name]);
  }

}

