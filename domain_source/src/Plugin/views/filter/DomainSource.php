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
      ] + \Drupal::service('domain.loader')->loadOptionsList();
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value['_active'])) {
      $active_id = \Drupal::service('domain.negotiator')->getActiveId();
      $this->value[$active_id] = $active_id;
    }
    unset($this->value['_active']);

    parent::query();
  }
}
