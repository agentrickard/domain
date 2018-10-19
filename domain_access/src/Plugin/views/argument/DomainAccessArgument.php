<?php

namespace Drupal\domain_access\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Argument handler to find nodes by domain assignment.
 *
 * @ViewsArgument("domain_access_argument")
 */
class DomainAccessArgument extends StringArgument {

  /**
   * {@inheritdoc}
   */
  public function title() {
    if ($domain = \Drupal::entityTypeManager()->getStorage('domain')->load($this->argument)) {
      return $domain->label();
    }
    return parent::title();
  }

}
