<?php

namespace Drupal\domain_access\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Provides filtering by assigned domain.
 *
 * @ViewsFilter("domain_access_filter")
 */
class DomainAccessFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // @TODO: filter this list.
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Domains');
      $this->valueOptions = \Drupal::entityTypeManager()->getStorage('domain')->loadOptionsList();
    }
    return $this->valueOptions;
  }

}
