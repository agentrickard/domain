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
    $account = $this->prepareUser($account);
    // Check the global permission.
    if ($account->hasPermission('administer domains')) {
      return AccessResult::allowed();
    }
    // @TODO: This may not be relevant.
    if ($operation == 'create' && $account->hasPermission('create domains')) {
      return AccessResult::allowed();
    }
    // For other operations, check that the user is a domain admin.
    $is_admin = $this->isDomainAdmin($entity, $account);
    if ($operation == 'view' && $is_admin && ($entity->status() || $account->hasPermission('access inactive domains'))) {
      return AccessResult::allowed();
    }
    if ($operation == 'update' && $account->hasPermission('edit assigned domains') && $is_admin) {
      return AccessResult::allowed();
    }
    if ($operation == 'delete' && $account->hasPermission('delete assigned domains') && $is_admin) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Checks if a user can administer a specific domain.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param \Drupal\Core\Session\AccountInterface
   *   The user account.
   *
   * @return boolean
   */
  public function isDomainAdmin(EntityInterface $entity, AccountInterface $account) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
    $user_domains = $this->getAdminValues($user);
    return isset($user_domains[$entity->id()]);
  }

  /**
   * Gets the values of a domain field from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   An array keyed by domain entity id.
   *
   * @TODO: Refactor this code so that it can be shared by other modules.
   */
  public function getAdminValues(EntityInterface $entity, $field_name = DOMAIN_ADMIN_FIELD) {
    $list = array();
    // In tests, $entity is returning NULL.
    if (is_null($entity)) {
      return $list;
    }
    // Get the values of an entity.
    $values = $entity->get($field_name);
    // Must be at least one item.
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          $list[$target['target_id']] = $target['target_id'];
        }
      }
    }
    return $list;
  }

}
