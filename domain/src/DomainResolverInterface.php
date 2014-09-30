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
  public function resolveActiveDomain();

  /**
   * Stores the inbound httpHost request.
   */
  public function setHttpHost($httpHost);

  /**
   * Gets the inbound httpHost request.
   */
  public function getHttpHost();

  /**
   * Gets the id of the active domain.
   */
  public function getActiveId();

  /**
   * Gets the hostname of the active request.
   */
  public function resolveActiveHostname();

}
