<?php

namespace Drupal\domain;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Provides an interface for domain entity storage.
 */
interface DomainStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Gets the default domain object.
   *
   * @return \Drupal\domain\DomainInterface|null
   *   The default domain record or NULL.
   */
  public function loadDefaultDomain();

  /**
   * Returns the id of the default domain.
   *
   * @return int|bool
   *   The id of the default domain or FALSE if none is set.
   */
  public function loadDefaultId();

  /**
   * Loads multiple domains and sorts by weight.
   *
   * @param array $ids
   *   An optional array of specific ids to load.
   *
   * @return \Drupal\domain\DomainInterface[]
   *   An array of domain records.
   */
  public function loadMultipleSorted(array $ids = NULL);

  /**
   * Loads a domain record by hostname lookup.
   *
   * @param string $hostname
   *   A hostname string, in the format example.com.
   *
   * @return \Drupal\domain\DomainInterface|null
   *   The domain record or NULL.
   */
  public function loadByHostname($hostname);

  /**
   * Returns the list of domains formatted for a form options list.
   *
   * @return array
   *   A weight-sorted array of id => label for use in forms.
   */
  public function loadOptionsList();

  /**
   * Sorts domains by weight.
   *
   * For use by loadMultipleSorted().
   *
   * @param DomainInterface $a
   *   The first Domain object to sort.
   * @param DomainInterface $b
   *   The Domain object to compare against.
   *
   * @return bool
   *   Wether the first domain weight is greater or not.
   */
  public function sort(DomainInterface $a, DomainInterface $b);

  /**
   * Gets the entity field schema for domain records.
   *
   * @return array
   *   An array representing the field schema of the object.
   */
  public function loadSchema();

  /**
   * Removes www. prefix from a hostname, if set.
   *
   * @param string $hostname
   *   A hostname.
   *
   * @return string
   *   The cleaned hostname.
   */
  public function prepareHostname($hostname);

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

  /**
   * Returns the default http/https scheme for the site.
   *
   * This function helps us account for variable schemes across environments.
   *
   * @return string
   *   A string representation of s scheme (http|https).
   */
  public function getDefaultScheme();

}
