<?php

/**
 * @file
 * Definition of Drupal\domain\DomainValidatorInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

/**
 * Supplies validator methods for common domain requests.
 */
interface DomainValidatorInterface {

  /**
   * Validates the hostname for a domain.
   */
  public function validate(DomainInterface $domain);

  /**
   * Tests that a domain responds correctly.
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
