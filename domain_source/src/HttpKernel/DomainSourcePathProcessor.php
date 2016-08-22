<?php

namespace Drupal\domain_source\HttpKernel;

use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
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
    $source = NULL;
    $options['active_domain'] = $active_domain;

    $entity = $this->getEntity($path, $options, 'node');

    // One hook for nodes.
    if (!empty($entity)) {
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
  public static function getEntity($path, $options, $type = 'node') {
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
        // Look! We're using arg() in Drupal 8 because we have to.
        $args = explode('/', $path);
        if (isset($args[$i])) {
          $entity = \Drupal::entityTypeManager()->getStorage($type)->load($args[$i]);
        }
      }
    }
    return $entity;
  }

}
