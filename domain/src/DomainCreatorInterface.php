<?php

/**
 * @file
 * Definition of Drupal\domain\DomainCreatorInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

/**
 * Handles the creation of new domain records.
 */
interface DomainCreatorInterface {

  /**
   * Creates a domain object for saving.
   *
   * @param array $values
   *   The values to assign to the domain record.
   *   Required values are: hostname, name.
   *   Required values may be omitted if $inherit is set to TRUE.
   * @param boolean $inherit
   *   Indicates whether to inherit certain values from the current HTTP request.
   *   These values are: hostname, name.
   *
   * @return DomainInterface $domain
   *   A domain record object.
   */
  public function createDomain(array $values = array(), $inherit = FALSE);

  /**
   * Creates a numeric id for the domain.
   *
   * The node access system still requires numeric keys.
   *
   * @return integer
   */
  public function createNextId();

  /**
   * Gets the hostname of the active request.
   *
   * This method is called if $inherit is set to TRUE.
   *
   * @return string
   *   The hostname string of the current request.
   */
  public function createHostname();

  /**
   * Creates a machine-name string from the hostname.
   *
   * This string is the primary key of the entity.
   *
   * @param string $hostname
   *   The hostname of the domain record.
   *
   * @return
   *   A string containing A-Z, a-z, 0-9, and _ characters.
   */
  public function createMachineName($hostname);

}
