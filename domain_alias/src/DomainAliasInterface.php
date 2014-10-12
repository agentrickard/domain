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

}
