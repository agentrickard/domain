<?php

namespace Drupal\domain_alias;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\domain\DomainInterface;

/**
 * Supplies storage methods for common domain_alias requests.
 */
interface DomainAliasStorageInterface extends ConfigEntityStorageInterface {

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
