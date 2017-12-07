<?php

namespace Drupal\domain_access\Plugin\Action;

/**
 * Removes a node to all affiliates..
 *
 * @Action(
 *   id = "domain_access_none_action",
 *   label = @Translation("Remove from all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessNone extends DomainAccessActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DOMAIN_ACCESS_ALL_FIELD, 0);
    $entity->save();
  }

}
