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

}
