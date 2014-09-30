<?php

<?php

/**
 * @file
 * Definition of Drupal\domain\DomainLoaderInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

/**
 * Supplies loader functions for common domain requests.
 */
interface DomainLoaderInterface {

  /**
   * Gets the default domain object.
   */
  public function getDefaultDomain();

  /**
   * Gets the default domain id.
   */
  public function getDefaultId();

  /**
   * Loads multiple domains.
   */
  public function loadMultiple($ids = array(), $reset = FALSE);

  /**
   * Loads multiple domains and sorts by weight.
   */
  public function loadMultipleSorted($ids = array());

  /**
   * Loads a domain record by hostname lookup.
   */
  public function loadByHostname($hostname);

  /**
   * Returns the list of domains formatted for a form options list.
   */
  public function optionsList();

  /**
   * Sorts domains by weight.
   */
  public function sort($a, $b);

  /**
   * Gets the schema for domain records.
   */
  public function getSchema();

}
