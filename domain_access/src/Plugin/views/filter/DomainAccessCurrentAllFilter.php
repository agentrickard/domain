<?php

namespace Drupal\domain_access\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_access_current_all_filter")
 */
class DomainAccessCurrentAllFilter extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->value_value = t('Available on current domain');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [1 => $this->t('Yes'), 0 => $this->t('No')];
  }

  /**
   * {@inheritdoc}
   */
  protected function operators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $all_table = $this->query->addTable('node__field_domain_all_affiliates', $this->relationship);
    $current_domain = \Drupal::service('domain.negotiator')->getActiveId();
    if (empty($this->value)) {
      // @TODO proper handling of NULL?
      $where = "$this->tableAlias.$this->realField <> '$current_domain'";
      $where = '(' . $where . " OR $this->tableAlias.$this->realField IS NULL)";
      $where = '(' . $where . " AND ($all_table.field_domain_all_affiliates_value = 0 OR $all_table.field_domain_all_affiliates_value IS NULL))";
    }
    else {
      $where = "($this->tableAlias.$this->realField = '$current_domain' OR $all_table.field_domain_all_affiliates_value = 1)";
    }
    $this->query->addWhereExpression($this->options['group'], $where);
    // This filter causes duplicates.
    $this->query->options['distinct'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'url.site';

    return $contexts;
  }

}
