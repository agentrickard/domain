<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\field\DomainAccessNode.
 */

namespace Drupal\domain_access\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Field;

/**
 * Field handler to present the link to a node on a domain.
 *
 * @ViewsField("domain_access_node")
 */
class DomainAccessNode extends Field {

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
