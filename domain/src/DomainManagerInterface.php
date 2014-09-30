<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManagerInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

interface DomainManagerInterface {

  /**
   * Determines the active domain request.
   */
  public function setRequestDomain($httpHost);

  /**
   * Sets the active domain.
   */
  public function setActiveDomain(DomainInterface $domain);

  /**
   * Gets the active domain.
   */
  public function getActiveDomain();

  /**
   * Stores the inbound httpHost request.
   */
  public function setHttpHost($httpHost);

  /**
   * Retrieves the inbound httpHost request.
   */
  public function getHttpHost();

  /**
   * Gets the schema for domain records.
   */
  public function getSchema();

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
   * Creates a new domain record object.
   */
  public function createDomain($inherit = FALSE, array $values = array());

  /**
   * Gets the next numeric id for a domain.
   */
  public function getNextId();

  /**
   * Gets the hostname of the active request.
   */
  public function getHostname();

  /**
   * Gets the machine name of a host, used as primary key.
   */
  public function getMachineName($hostname);

  /**
   * Gets the id of the active domain.
   */
  public function getActiveId();

  /**
   * Returns the list of domains formatted for a form options list.
   */
  public function optionsList();

  /**
   * Sorts domains by weight.
   */
  public function sort($a, $b);

  /**
   * Gets the list of required fields.
   */
  public function getRequiredFields();

}
