<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Assigns a node to all affiliates.
 *
 * @Action(
 *   id = "domain_access_all_action",
 *   label = @Translation("Assign to all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessAll extends DomainAccessSimpleBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD, 1);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $entity) {
      if (!empty($entity) && $entity->hasField(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD)) {
        $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD, 1);
        $entity->save();
      }
    }
  }

}
