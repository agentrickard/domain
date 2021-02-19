<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Removes a node to all affiliates..
 *
 * @Action(
 *   id = "domain_access_none_action",
 *   label = @Translation("Remove from all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessNone extends DomainAccessSimpleBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD, 0);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $entity) {
      if (!empty($entity) && $entity->hasField(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD)) {
        $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD, 0);
        $entity->save();
      }
    }
  }

}
