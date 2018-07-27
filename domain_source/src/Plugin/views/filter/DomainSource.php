<?php

namespace Drupal\domain_source\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_source")
 */
class DomainSource extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Domains');
      $this->valueOptions = [
        '_active' => $this->t('Active domain'),
      ] + \Drupal::entityTypeManager()->getStorage('domain')->loadOptionsList();
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $active_index = array_search('_active', (array) $this->value);
    if ($active_index !== FALSE) {
      $active_id = \Drupal::service('domain.negotiator')->getActiveId();
      $this->value[$active_index] = $active_id;
    }

    parent::query();
  }

}
