<?php

namespace Drupal\domain_alias;

use Drupal\domain\DomainInterface;

/**
 * Supplies loader methods for common domain_alias requests.
 *
 * @deprecated
 *  This interface will be removed before the 8.1.0 release.
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
  public function loadMultiple(array $ids = NULL, $reset = FALSE);

  /**
   * Loads a domain alias record by hostname lookup.
   *
   * This method will return the best match to a request.
   *
   * @param string $hostname
   *   A hostname string, in the format example.com.
   *
   * @return \Drupal\domain_alias\DomainAliasInterface|null
   *   The best match alias record for the provided hostname.
   */
  public function loadByHostname($hostname);

  /**
   * Loads a domain alias record by pattern lookup.
   *
   * @param string $pattern
   *   A pattern string, in the format *.example.com.
   *
   * @return \Drupal\domain_alias\DomainAliasInterface|null
   *   The domain alias record given a pattern string.
   */
  public function loadByPattern($pattern);

  /**
   * Loads an array of domain alias record by environment lookup.
   *
   * @param string $environment
   *   An environment string, e.g. 'default' or 'local'.
   *
   * @return array
   *   An array of \Drupal\domain_alias\DomainAliasInterface objects.
   */
  public function loadByEnvironment($environment);

  /**
   * Loads a domain alias record by pattern lookup.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain entity.
   * @param string $environment
   *   An environment string, e.g. 'default' or 'local'.
   *
   * @return array
   *   An array of \Drupal\domain_alias\DomainAliasInterface objects.
   */
  public function loadByEnvironmentMatch(DomainInterface $domain, $environment);

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
