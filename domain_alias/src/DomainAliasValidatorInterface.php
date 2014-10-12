<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasValidatorInterface.
 */

namespace Drupal\domain_alias;

use Drupal\domain_alias\DomainAliasInterface;

/**
 * Supplies validator methods for common domain requests.
 */
interface DomainAliasValidatorInterface {

  /**
   * Validates the rules for a domain alias.
   */
  public function validate(DomainAliasInterface $alias);

}
