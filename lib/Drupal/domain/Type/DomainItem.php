<?php

/**
 * @file
 * Definition of Drupal\domain\Type\DomainItem.
 */

namespace Drupal\domain\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'domain' entity field items.
 */
class DomainItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see TextItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['realm'] = array(
        'type' => 'varchar',
        'label' => t('Domain realm'),
      );
      static::$propertyDefinitions['gid'] = array(
        'type' => 'integer',
        'label' => t('Domain record id'),
      );
    }
    return static::$propertyDefinitions;
  }
}
