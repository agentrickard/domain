<?php

/**
 * @file
 * Definition of Drupal\domain\Type\DomainAliasItem.
 */

namespace Drupal\domain_alias\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'domain' entity field items.
 */
class DomainAliasItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['pattern'] = array(
        'type' => 'string',
        'constraints' => array(
          'EntityType' => 'domain',
        ),
        'label' => t('Pattern'),
        'description' => t('The referenced domain record'),
      );
      static::$propertyDefinitions['redirect'] = array(
        'type' => 'integer',
        'label' => t('Redirect'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given.
    if (!is_array($values)) {
      $values = array('entity' => $values);
    }

    // Entity is computed out of the ID, so we only need to update the ID. Only
    // set the entity field if no ID is given.
    if (isset($values['domain_id'])) {
      $this->properties['domain_id']->setValue($values['domain_id']);
    }
    elseif (isset($values['entity'])) {
      $this->properties['entity']->setValue($values['entity']);
    }
    else {
      $this->properties['entity']->setValue(NULL);
    }
    unset($values['entity'], $values['domain_id']);
    if ($values) {
      throw new \InvalidArgumentException('Property ' . key($values) . ' is unknown.');
    }
  }

}
