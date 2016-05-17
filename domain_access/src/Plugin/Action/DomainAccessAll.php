<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Assigns a node to all affiliates.
 *
 * @Action(
 *   id = "domain_access_all_action",
 *   label = @Translation("Assign to all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessAll extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DOMAIN_ACCESS_ALL_FIELD, 1);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    // @TODO: Check this logic.
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
