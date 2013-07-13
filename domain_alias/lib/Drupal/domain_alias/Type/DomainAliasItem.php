<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Type\DomainAliasItem.
 */

namespace Drupal\domain_alias\Type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\Plugin\Core\Entity\Field;

/**
 * Defines the 'domain' entity field items.
 */
class DomainAliasItem extends ConfigFieldItemBase {

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

  public static function schema(Field $field) {
    $definition = \Drupal::service('plugin.manager.entity.field.field_type')->getDefinition($field->type);
    $module = $definition['module'];
    module_load_install($module);
    $callback = "{$module}_field_schema";
    if (function_exists($callback)) {
      return $callback($field);
    }
  }

}
