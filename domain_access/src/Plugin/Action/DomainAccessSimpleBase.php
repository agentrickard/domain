<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base class for permissions for actions that are not configurable.
 */
abstract class DomainAccessSimpleBase extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access_result = AccessResult::allowedIfHasPermission($account, 'administer domains');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
