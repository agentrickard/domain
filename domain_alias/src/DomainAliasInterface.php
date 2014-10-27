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

  public function getPattern();
  public function getDomainId();
  public function getRedirect();

}
