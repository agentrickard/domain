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
   * Get the matching pattern value for an alias record.
   *
   * @return string
   */
  public function getPattern();

  /**
   * Get the numeric domain_id value for an alias record.
   *
   * @return integer
   */
  public function getDomainId();

  /**
   * Get the redirect value (301|302|NULL) for an alias record.
   *
   * @return integer
   */
  public function getRedirect();

}
