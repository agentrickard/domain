<?php

namespace Drupal\domain_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
interface DomainAccessManagerInterface {

  /**
   * Get the domain access field values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   The domain access field values.
   */
  public function getAccessValues(EntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD);

  /**
   * Get the all affiliates field values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return bool
   *   Returns TRUE if the entity is sent to all affiliates.
   */
  public function getAllValue(EntityInterface $entity);

  /**
   * Compare the entity values against a user's account assignments.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being checked for access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user performing the action.
   *
   * @return bool
   *   Returns TRUE if the user has access to the domain.
   */
  public function checkEntityAccess(EntityInterface $entity, AccountInterface $account);

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
   * Get the default all affiliates value for an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being created.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field being created.
   *
   * @return array
   *   The default all affiliates value(s).
   */
  public static function getDefaultAllValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition);

}
