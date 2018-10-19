<?php

namespace Drupal\domain_access;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
interface DomainAccessManagerInterface {

  /**
   * Get the domain access field values from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   The domain access field values.
   */
  public static function getAccessValues(FieldableEntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD);

  /**
   * Get the all affiliates field values from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return bool
   *   Returns TRUE if the entity is sent to all affiliates.
   */
  public static function getAllValue(FieldableEntityInterface $entity);

  /**
   * Compare the entity values against a user's account assignments.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being checked for access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user performing the action.
   *
   * @return bool
   *   Returns TRUE if the user has access to the domain.
   */
  public function checkEntityAccess(FieldableEntityInterface $entity, AccountInterface $account);

  /**
   * Get the default field value for an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being created.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field being created.
   *
   * @return array
   *   The default field value(s).
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition);

  /**
   * Checks that a user belongs to the domain and has a set of permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain being checked.
   * @param array $permissions
   *   The relevant permissions to check.
   * @param string $conjunction
   *   The conjunction AND|OR to use when checking permissions.
   *
   * @return bool
   *   Returns TRUE if the user is assigned to the domain and has the necessary
   *   permissions.
   */
  public function hasDomainPermissions(AccountInterface $account, DomainInterface $domain, array $permissions, $conjunction = 'AND');

  /**
   * Get all possible URLs pointing to an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return array
   *   An array of absolute URLs keyed by domain_id, with an known canonical id
   *   as the first element of the array.
   */
  public function getContentUrls(FieldableEntityInterface $entity);

}
