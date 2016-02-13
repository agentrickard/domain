<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\filter\DomainAccessCurrentAllFilter.
 */

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
    $this->valueOptions = array(1 => $this->t('Yes'), 0 => $this->t('No'));
  }

  /**
   * {@inheritdoc}
   */
  protected function operators() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $where = "$this->tableAlias.$this->realField ";
    $current_domain = \Drupal::service('domain.negotiator')->getActiveId();

    if (empty($this->value)) {
      $where .= "<> '$current_domain'";
      if ($this->accept_null) {
        $where = '(' . $where . " OR $this->tableAlias.$this->realField IS NULL)";
      }
    }
    else {
      $where .= "= '$current_domain'";
    }
    $this->query->addWhereExpression($this->options['group'], $where);
  }

}
