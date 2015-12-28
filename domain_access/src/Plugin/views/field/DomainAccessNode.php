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
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainInterface;

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

    $this->additional_fields['nid'] = array('table' => 'node_field_data', 'field' => 'nid');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $id = $this->getValue($values, 'field_domain_access_target_id');
    $nid = $this->getValue($values, 'nid');
    $domain = \Drupal::service('domain.loader')->load($id);
    $link = \Drupal::url('entity.node.canonical', ['node' => $nid], ['absolute' => FALSE]);
    #return \Drupal::l($this->sanitizeValue($domain->label()), $domain->getPath() . trim($link, '/'));
    return [
      '#type' => 'link',
      '#url' => $domain->getPath() . trim($link, '/'),
      '#title' => $domain->label(),
    ];

  }

}
