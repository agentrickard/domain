<?php

namespace Drupal\domain;

/**
 * Handles the negotiation of the active domain record.
 */
interface DomainNegotiatorInterface {

  /**
   * Determines the active domain request.
   *
   * The negotiator is passed an httpHost value, which is checked against domain
   * records for a match.
   *
   * @param string $httpHost
   *   A string representing the hostname of the request (e.g. example.com).
   * @param bool $reset
   *   Indicates whether to reset the internal cache.
   */
  public function setRequestDomain($httpHost, $reset = FALSE);

  /**
   * Sets the active domain.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   Sets the domain record as active for the duration of that request.
   */
  public function setActiveDomain(DomainInterface $domain);

  /**
   * Stores the inbound httpHost request.
   *
   * @param string $httpHost
   *   A string representing the hostname of the request (e.g. example.com).
   */
  public function setHttpHost($httpHost);

  /**
   * Gets the inbound httpHost request.
   *
   * @return string
   *   A string representing the hostname of the request (e.g. example.com).
   */
  public function getHttpHost();

  /**
   * Gets the id of the active domain.
   *
   * @return int|null|string
   */
  public function getActiveId();

  /**
   * Sets the hostname of the active request.
   *
   * This method is an internal method for use by the public getActiveDomain()
   * call. It is responsible for determining the active hostname of the request
   * and then passing that data to the negotiator.
   *
   * @return string
   */
  public function negotiateActiveHostname();

  /**
   * Gets the active domain.
   *
   * This method should be called by external classes using the negotiator
   * service.
   *
   * @param bool $reset
   *   Reset the internal cache of the active domain.
   * @return DomainInterface
   */
  public function getActiveDomain($reset = FALSE);

}
