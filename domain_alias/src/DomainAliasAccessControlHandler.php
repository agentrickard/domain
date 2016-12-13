<?php

namespace Drupal\domain_alias;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the domain alias entity type.
 *
 * Note that this is not a node access check.
 */
class DomainAliasAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);
    // Check the global permission.
    if ($account->hasPermission('administer domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'create' && $account->hasPermission('create domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'update' && $account->hasPermission('edit domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'delete' && $account->hasPermission('delete domain aliases')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
