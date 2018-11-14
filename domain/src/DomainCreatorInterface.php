<?php

namespace Drupal\domain;

/**
 * Handles the creation of new domain records.
 *
 * @deprecated
 *  This interface will be removed before the 8.1.0 release.
 */
interface DomainCreatorInterface {

  /**
   * Creates a domain object for saving.
   *
   * @param array $values
   *   The values to assign to the domain record.
   *   Required values are: hostname, name.
   *   Passing an empty array will create a domain from the current request.
   *
   * @return \Drupal\domain\DomainInterface
   *   A domain record object.
   */
  public function createDomain(array $values = []);

  /**
   * Gets the hostname of the active request.
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
   *   The hostname of the domain record. If empty, the current request will be
   *   used.
   *
   * @return string
   *   A string containing A-Z, a-z, 0-9, and _ characters.
   */
  public function createMachineName($hostname = NULL);

}
