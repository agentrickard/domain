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
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if (user_access('administer domains', $account)) {
      return TRUE;
    }
    if ($operation == 'create' && user_access('create domains', $account)) {
      return TRUE;
    }
    return parent::access($entity, $operation, $langcode, $account);
  }
}
