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
    if ($this->getDrupalVersion() > 8) {
      $definition->setClass('Drupal\domain_source\EventSubscriber\DomainSourceRedirectResponseSubscriber');
    }
    else {
      $definition->setClass('Drupal\domain_source\EventSubscriber\DomainSourceRedirectResponseSubscriberD8');
    }
  }

  /**
   * Determines the Drupal version.
   *
   * @return integer
   *  The core numberic version.
   */
  private function getDrupalVersion() {
    return (int) substr(\Drupal::VERSION, 0, 1);
  }

}
