<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigServiceProvider.
 */

namespace Drupal\domain_config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the router.route_provider service to point to our customized one.
 */
class DomainConfigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('router.route_provider');
    $definition->setClass('Drupal\domain_config\Routing\DomainRouteProvider');
  }
}