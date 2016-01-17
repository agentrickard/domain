<?php

/**
 * @file
 * Definition of Drupal\domain_access\DomainAccessManagerInterface.
 */

namespace Drupal\domain_access;

use Drupal\domain\DomainInterface;
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
   * @param Drupal\Core\Entity\EntityInterface
   *   The entity to retrieve field data from.
   * @param $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   */
  public function getAccessValues(EntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD);

  /**
   * Get the all affiliates field values from an entity.
   *
   * @param Drupal\Core\Entity\EntityInterface
   *   The entity to retrieve field data from.
   *
   * @return boolean
   */
  public function getAllValue(EntityInterface $entity);

  /**
   * Compare the entity values against a user's account assignments.
   *
   * @param Drupal\Core\Entity\EntityInterface
   *   The entity being checked for access.
   * @param Drupal\Core\Session\AccountInterface
   *   The account of the user performing the action.
   *
   * @return boolean
   */
  public function checkEntityAccess(EntityInterface $entity, AccountInterface $account);

  /**
   * Get the default field value for an entity.
   *
   * @param Drupal\Core\Entity\FieldableEntityInterface
   *   The entity being created.
   * @param Drupal\Core\Field\FieldDefinitionInterface
   *   The field being created.
   *
   * @return array
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition);

  /**
   * Get the default all affiliates value for an entity.
   *
   * @param Drupal\Core\Entity\FieldableEntityInterface
   *   The entity being created.
   * @param Drupal\Core\Field\FieldDefinitionInterface
   *   The field being created.
   *
   * @return array
   */
  public static function getDefaultAllValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition);

}
