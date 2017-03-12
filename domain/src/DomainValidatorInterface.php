<?php

namespace Drupal\domain;

/**
 * Supplies validator methods for common domain requests.
 */
interface DomainValidatorInterface {

  /**
   * Validates the hostname for a domain.
   *
   * @param string $hostname
   *   A hostname to validate.
   *
   * @return array
   *   An array of validation errors. An empty array indicates a valid domain.
   */
  public function validate($hostname);

  /**
   * Tests that a domain responds correctly.
   *
   * This is a server-level configuration test. The core module provides an
   * image file that we use to test the validity of domain-generated URLs.
   *
   * That file is /domain/tests/200.png.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain record.
   *
   * @return int
   *   The server response code for the request.
   */
  public function checkResponse(DomainInterface $domain);

  /**
   * Returns the properties required to create a domain record.
   *
   * @return array
   *   Array of property names.
   */
  public function getRequiredFields();

}
