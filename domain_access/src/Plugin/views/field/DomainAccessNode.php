<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\field\DomainAccessNode.
 */

namespace Drupal\domain_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Field;
#use Drupal\views\Plugin\views\field\FieldPluginBase;
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
class DomainAccessNode extends Field {

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
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    // Override the default link generator, which wants to send us to the entity
    // page, not the node we are looking at.
    foreach ($items as &$item) {
      $object = $item['raw'];
      $node = $object->getEntity();
      $url = $node->toUrl()->toString();
      $domain = $item['rendered']['#options']['entity'];
      $item['rendered']['#type'] = 'markup';
      $item['rendered']['#markup'] = '<a href="' . $domain->buildUrl($url) . '">' . $domain->label() . '</a>';
    }
    return $items;
  }

}
