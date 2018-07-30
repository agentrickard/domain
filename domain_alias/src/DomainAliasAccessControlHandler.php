<?php

namespace Drupal\domain_alias;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainAccessControlHandler;

/**
 * Defines the access controller for the domain alias entity type.
 */
class DomainAliasAccessControlHandler extends DomainAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);
    // Check the global permission.
    if ($account->hasPermission('administer domain aliases')) {
      return AccessResult::allowed();
    }
    // For other actions we allow admin if they can administer the parent
    // domains.
    $domain = $entity->getDomain();
    // If this account can administer the domain, allow access to actions based
    // on permission.
    if ($is_admin = $this->isDomainAdmin($domain, $account)) {
      if ($operation == 'view' && $account->hasPermission('view domain aliases')) {
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
    }
    return AccessResult::forbidden();
  }

}
