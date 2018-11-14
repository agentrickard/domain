<?php

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Custom access control handler for the domain overview page.
 */
class DomainListCheck {

  /**
   * Handles route permissions on the domain list page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the route request.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public static function viewDomainList(AccountInterface $account) {
    if ($account->hasPermission('administer domains') || $account->hasPermission('view domain list') || $account->hasPermission('view assigned domains')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
