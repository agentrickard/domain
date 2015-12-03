<?php

/**
 * @file
 * Definition of Drupal\domain_access\DomainAccessManagerInterface.
 */

namespace Drupal\domain_access;

use Drupal\domain\DomainInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
interface DomainAccessManagerInterface {

  /**
   * Get the domain access field values from an entity.
   *
   * @return array
   */
  public function getAccessValues(EntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD) {}

  /**
   * Get the all affiliates field values from an entity.
   *
   * return boolean
   */
  public function getAllValue(EntityInterface $entity) {}

  /**
   * Compare the entity values against a user's account assignments.
   *
   * return boolean
   */
  public function checkEntity(EntityInterface $entity, AccountInterface $account) {}

  /**
   * Get the default field value for an entity.
   *
   * @return array
   */
  public function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {}

  /**
   * Get the default all affiliates value for an entity.
   *
   * @return array
   */
  public function getDefaultAllValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {}

}
