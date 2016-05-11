<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasValidatorInterface.
 */

namespace Drupal\domain_alias;


/**
 * Supplies validator methods for common domain requests.
 */
interface DomainAliasValidatorInterface {

  /**
   * Validates the rules for a domain alias.
   * 
   * @param \Drupal\domain_alias\DomainAliasInterface $alias
   */
  public function validate(DomainAliasInterface $alias);

}
