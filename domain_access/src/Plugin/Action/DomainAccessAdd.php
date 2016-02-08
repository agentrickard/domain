<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\Action\DomainAccessAdd.
 */

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Assigns a node to all affiliates.
 *
 * @Action(
 *   id = "domain_access_add_action",
 *   label = @Translation("Add domain to content"),
 *   type = "node"
 * )
 *
 * @see user_user_role_insert() user_user_role_delete().
 */
class DomainAccessAdd extends ActionBase {

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
      ->andIf($object->status->access('edit', $account, TRUE))
      ->andIf($account->hasPermission('publish to any domain'));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
