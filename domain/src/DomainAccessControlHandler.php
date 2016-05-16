<?php

namespace Drupal\domain;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the domain entity type.
 *
 * Note that this is not a node access check.
 */
class DomainAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // Check the global permission.
    if ($account->hasPermission('administer domains')) {
      return AccessResult::allowed();
    }
    if ($operation == 'view' && ($entity->status() || $account->hasPermission('access inactive domains'))) {
      return AccessResult::allowed();
    }
    if ($operation == 'create' && $account->hasPermission('create domains')) {
      return AccessResult::allowed();
    }
    // @TODO: assign users to domains and check.
    if ($operation == 'update' && $account->hasPermission('edit assigned domains')) {
      return AccessResult::allowed();
    }
    // @TODO: assign users to domains and check.
    if ($operation == 'delete' && $account->hasPermission('delete assigned domains')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
