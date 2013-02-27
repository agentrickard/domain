<?php

/**
 * @file
 * Definition of Drupal\domain\DomainBundle.
 */

namespace Drupal\domain;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The bundle for domain.module.
 */
class DomainBundle extends Bundle {

  public function build(ContainerBuilder $container) {
    $container->register('domain.domain_subscriber', 'Drupal\domain\DomainSubscriber')
      ->addTag('event_subscriber');
  }

}
