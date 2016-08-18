<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Removes a user from all affiliates.
 *
 * @Action(
 *   id = "domain_access_edit_none_action",
 *   label = @Translation("Remove editors from all affiliates"),
 *   type = "user"
 * )
 */
class DomainAccessEditNone extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DOMAIN_ACCESS_ALL_FIELD, 0);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // @TODO: Check this logic.
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->roles->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
