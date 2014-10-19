<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasInterface.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a domain alias entity.
 */
interface DomainAliasInterface extends ConfigEntityInterface {

  /**
   * Gets a property from the domain alias.
   *
   * @param $name
   *  The name of the property to retrieve.
   */
  public function getProperty($name);

  /**
   * Sets a specific domain alias attribute.
   *
   * @param $name
   *   The property key to save for the $domain object.
   * @param $value
   *   The value to set for the property.
   *
   */
  public function setProperty($name, $value);

}
