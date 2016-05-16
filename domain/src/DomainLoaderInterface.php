<?php

/**
 * @file
 * Definition of Drupal\domain\DomainLoaderInterface.
 */

namespace Drupal\domain;


/**
 * Supplies loader methods for common domain requests.
 */
interface DomainLoaderInterface {

  /**
   * Loads a single domains.
   *
   * @param int $id
   *   A domain id to load.
   * @param bool $reset
   *   Indicates that the entity cache should be reset.
   *
   * @return DomainInterface
   *   A Drupal\domain\DomainInterface object | NULL.
   */
  public function load($id, $reset = FALSE);

  /**
   * Gets the default domain object.
   *
   * @return \Drupal\domain\DomainInterface | NULL
   *   The default domain object or NULL.
   */
  public function loadDefaultDomain();

  /**
   * Returns the id of the default domain.
   *
   * @return int
   *   The id of the default domain or FALSE if none is set.
   */
  public function loadDefaultId();

  /**
   * Loads multiple domains.
   *
   * @param array $ids
   *   An optional array of specific ids to load.
   * @param bool $reset
   *   Indicates that the entity cache should be reset.
   *
   * @return array
   *   An array of Drupal\domain\DomainInterface objects.
   */
  public function loadMultiple($ids = NULL, $reset = FALSE);

  /**
   * Loads multiple domains and sorts by weight.
   *
   * @param array $ids
   *   An optional array of specific ids to load.
   *
   * @return array
   *   An array of Drupal\domain\DomainInterface objects.
   */
  public function loadMultipleSorted($ids = NULL);

  /**
   * Loads a domain record by hostname lookup.
   *
   * @param string $hostname
   *   A hostname string, in the format example.com.
   *
   * @return \Drupal\domain\DomainInterface | NULL
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

}
