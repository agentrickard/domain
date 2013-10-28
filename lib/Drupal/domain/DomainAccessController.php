<?php

/**
 * @file
 * Contains \Drupal\domain\DomainAccessController.
 */

namespace Drupal\domain;

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the domain entity type.
 */
class DomainAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if ($account->hasPermission($this->entityInfo['admin_permission'])) {
      return TRUE;
    }
    if ($operation == 'create' && $account->hasPermission('create domains')) {
      return TRUE;
    }
    // @TODO: assign users to domains.
    if ($operation == 'edit' && $account->hasPermission('edit assigned domains')) {
      return TRUE;
    }
    if ($operation == 'delete' && $account->hasPermission('edit assigned domains')) {
      return TRUE;
    }
    return FALSE;
  }
}
