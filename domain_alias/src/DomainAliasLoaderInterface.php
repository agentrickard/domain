<?php

namespace Drupal\domain_alias;

/**
 * Supplies loader methods for common domain_alias requests.
 */
interface DomainAliasLoaderInterface {

  /**
   * Loads a single alias.
   *
   * @param string $id
   *   A domain_alias id to load.
   * @param bool $reset
   *   Indicates that the entity cache should be reset.
   *
   * @return DomainAliasInterface
   *   A Drupal\domain_alias\DomainAliasInterface object | NULL.
   */
  public function load($id, $reset = FALSE);

  /**
   * Loads multiple aliases.
   *
   * @param array $ids
   *   An optional array of specific ids to load.
   * @param bool $reset
   *   Indicates that the entity cache should be reset.
   *
   * @return array
   *   An array of Drupal\domain_alias\DomainAliasInterface objects.
   */
  public function loadMultiple($ids = NULL, $reset = FALSE);

  /**
   * Loads a domain alias record by hostname lookup.
   *
   * This method will return the best match to a request.
   *
   * @param string $hostname
   *   A hostname string, in the format example.com.
   *
   * @return \Drupal\domain_alias\DomainAliasInterface | NULL
   *   The best match alias record for the provided hostname.
   */
  public function loadByHostname($hostname);

  /**
   * Loads a domain alias record by pattern lookup.
   *
   * @param string $pattern
   *   A pattern string, in the format *.example.com.
   *
   * @return \Drupal\domain_alias\DomainAliasInterface | NULL
   *   The domain alias record given a pattern string.
   */
  public function loadByPattern($pattern);

  /**
   * Sorts aliases by wildcard to float exact matches to the top.
   *
   * For use by loadByHostname().
   */
  public function sort($a, $b);

  /**
   * Gets the schema for domain alias records.
   *
   * @return array
   *   An array representing the field schema of the object.
   */
  public function loadSchema();

}
