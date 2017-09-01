<?php

namespace Drupal\domain;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Provides services for the domain module that extend core functionality.
 */
class DomainServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('redirect_response_subscriber');
    $definition->setClass('Drupal\domain\EventSubscriber\DomainRedirectResponseSubscriber');
  }
}
