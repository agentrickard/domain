<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Assigns a user to all affiliates.
 *
 * @Action(
 *   id = "domain_access_edit_all_action",
 *   label = @Translation("Assign editors to all affiliates"),
 *   type = "user"
 * )
 */
class DomainAccessEditAll extends ActionBase {

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
    // @TODO: Check this logic.
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->roles->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
