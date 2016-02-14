<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\argument\DomainAccessArgument.
 */

namespace Drupal\domain_access\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Field handler to present the link an entity on a domain.
 *
 * @ViewsArgument("domain_access_argument")
 */
class DomainAccessArgument extends StringArgument {

  /**
   * @inheritdoc
   */
  function title() {
    if ($domain = \Drupal::service('domain.loader')->load($this->argument)) {
      return $domain->label();
    }
    return parent::title();
  }
}
