<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\field\DomainAccessNode.
 */

namespace Drupal\domain_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present the link to a node on a domain.
 *
 * @ViewsField("domain_access_node")
 */
class DomainAccessNode extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields[DOMAIN_ACCESS_FIELD] = DOMAIN_ACCESS_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
   # $options['absolute'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values, DOMAIN_ACCESS_FIELD);
    return $nid;
    return \Drupal::url('entity.node.canonical', ['node' => $nid], ['absolute' => $this->options['absolute']]);
  }

}
