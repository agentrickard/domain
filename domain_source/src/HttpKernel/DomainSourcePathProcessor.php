<?php

namespace Drupal\domain_source\HttpKernel;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the outbound path using path alias lookups.
 */
class DomainSourcePathProcessor implements OutboundPathProcessorInterface {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An array of content entity types.
   *
   * @var array
   */
  protected $entityTypes;

  /**
   * An array of routes exclusion settings, keyed by route.
   *
   * @var array
   */
  protected $excludedRoutes;

  /**
   * The active domain request.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $activeDomain;

  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Constructs a DomainSourcePathProcessor object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory) {
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->configFactory = $config_factory;
    $this->domainStorage = $entity_type_manager->getStorage('domain');
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Load the active domain if not set.
    if (empty($options['active_domain'])) {
      $active_domain = $this->getActiveDomain();
    }
    else {
      $active_domain = $options['active_domain'];
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
    $alias = $this->aliasManager->getPathByAlias($path, $langcode);
    $url = Url::fromUserInput($alias, $options);

    // Check the route, if available. Entities can be configured to
    // only rewrite specific routes.
    if ($url->isRouted() && $this->allowedRoute($url->getRouteName())) {
      // Load the entity to check.
      if (!empty($options['entity'])) {
        $entity = $options['entity'];
      }
      else {
        $parameters = $url->getRouteParameters();
        if (!empty($parameters)) {
          $entity = $this->getEntity($parameters);
        }
      }
    }

    // One hook for entities.
    if (!empty($entity)) {
      // Enmsure we send the right translation.
      if (!empty($langcode) && method_exists($entity, 'hasTranslation') && $entity->hasTranslation($langcode) && $translation = $entity->getTranslation($langcode)) {
        $entity = $translation;
      }
      if ($target_id = domain_source_get($entity)) {
        $source = $this->domainStorage->load($target_id);
      }
      $options['entity'] = $entity;
      $options['entity_type'] = $entity->getEntityTypeId();
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
   * @param array $parameters
   *   An array of route parameters.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the entity when available, otherwise NULL.
   */
  public function getEntity(array $parameters) {
    $entity = NULL;
    $entity_type = key($parameters);
    $entity_types = $this->getEntityTypes();
    foreach ($parameters as $entity_type => $value) {
      if (!empty($entity_type) && isset($entity_types[$entity_type])) {
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($value);
      }
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
   * @return bool
   *   Returns TRUE when allowed, otherwise FALSE.
   */
  public function allowedRoute($name) {
    $excluded = $this->getExcludedRoutes();
    $parts = explode('.', $name);
    $route_name = end($parts);
    // Config is stored as an array. Empty items are not excluded.
    return !isset($excluded[$route_name]);
  }

  /**
   * Gets an array of content entity types, keyed by type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of content entity types, keyed by type.
   */
  public function getEntityTypes() {
    if (!isset($this->entityTypes)) {
      foreach ($this->entityTypeManager->getDefinitions() as $type => $definition) {
        if ($definition->getGroup() == 'content') {
          $this->entityTypes[$type] = $type;
        }
      }
    }
    return $this->entityTypes;
  }

  /**
   * Gets the settings for domain source path rewrites.
   *
   * @return array
   *   The settings for domain source path rewrites.
   */
  public function getExcludedRoutes() {
    if (!isset($this->excludedRoutes)) {
      $config = $this->configFactory->get('domain_source.settings');
      $routes = $config->get('exclude_routes');
      if (is_array($routes)) {
        $this->excludedRoutes = array_flip($routes);
      }
      else {
        $this->excludedRoutes = [];
      }
    }
    return $this->excludedRoutes;
  }

  /**
   * Gets the active domain.
   *
   * @return \Drupal\domain\DomainInterface
   *   The active domain.
   */
  public function getActiveDomain() {
    if (!isset($this->activeDomain)) {
      // Ensure that the loader has run.
      // In some tests, the kernel event has not.
      $active = $this->negotiator->getActiveDomain();
      if (empty($active)) {
        $active = $this->negotiator->getActiveDomain(TRUE);
      }
      $this->activeDomain = $active;
    }
    return $this->activeDomain;
  }

  /**
   * Get all possible URLs pointing to a node.
   *
   * @param $entity
   *   An entity object.
   *
   * @return array
   *   An array of absolute URLs keyed by domain_id, with an known canonical id
   *   as the first element of the array.
   */
  public function getContentUrls($entity) {
    // @TODO: inject this service.
    $manager = \Drupal::service('domain_access.manager');
    $domains = $manager->getAccessValues($entity);
    $source = domain_source_get($entity);
    if (isset($domains[$source])) {
      unset($domains['source']);
    }
    $list = [];
    if (!empty($source)) {
      $list[] = $source;
    }
    $list = array_merge($list, array_keys($domains));
    // @TODO: inject.
    $storage = \Drupal::\Drupal::entityTypeManager()->getStorage('domain');
    $domains = $storage->loadMultiple($list);
    $urls = [];
    foreach ($domains as $domain) {
      $options['active_domain'] = $domain->getPath();
      $url = $entity->toUrl('canonical', $options);
      $urls[$domain->id()] = $url;
    }

    return $urls;
  }
}
