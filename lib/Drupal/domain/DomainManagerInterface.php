<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManagerInterface.
 */

namespace Drupal\domain;

use Drupal\domain\Plugin\Core\Entity\Domain;

interface DomainManagerInterface {

  /**
   * Determines the active domain request.
   */
  public function requestDomain($httpHost);

  /**
   * Sets the active domain.
   */
  public function setActiveDomain(Domain $domain);

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
