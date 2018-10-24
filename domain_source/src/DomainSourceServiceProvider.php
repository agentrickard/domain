<?php

namespace Drupal\domain_source;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Provides services for the domain module that extend core functionality.
 */
class DomainSourceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('redirect_response_subscriber');
    $definition->setClass('Drupal\domain_source\EventSubscriber\DomainSourceRedirectResponseSubscriber');
  }

}
