<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\views\field\DomainAccessField.
 */

namespace Drupal\domain_access\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Field;

/**
 * Field handler to present the link an entity on a domain.
 *
 * @ViewsField("domain_access_field")
 */
class DomainAccessField extends Field {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    // Override the default link generator, which wants to send us to the entity
    // page, not the node we are looking at.
    foreach ($items as &$item) {
      $object = $item['raw'];
      $entity = $object->getEntity();
      $url = $entity->toUrl()->toString();
      $domain = $item['rendered']['#options']['entity'];
      $item['rendered']['#type'] = 'markup';
      $item['rendered']['#markup'] = '<a href="' . $domain->buildUrl($url) . '">' . $domain->label() . '</a>';
    }
    uasort($items, array($this, 'sort'));
    return $items;
  }

  private function sort($a, $b) {
    $domainA = $a['rendered']['#options']['entity'];
    $domainB = $b['rendered']['#options']['entity'];
    return $domainA->getWeight() > $domainB->getWeight();
  }

}
