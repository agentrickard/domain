<?php

/**
 * @file
 * Definition of Drupal\domain\DomainResolverInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

/**
 * Handles the negotation of the active domain record.
 */
interface DomainResolverInterface {

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
   * Gets the list of required fields.
   */
  public function getRequiredFields();

}
