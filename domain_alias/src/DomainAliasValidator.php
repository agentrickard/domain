<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasValidator.
 */

namespace Drupal\domain_alias;

use Drupal\domain_alias\DomainAliasInterface;
use Drupal\domain_alias\DomainAliasValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Supplies validator methods for common domain requests.
 */
class DomainAliasValidator implements DomainAliasValidatorInterface {

  use StringTranslationTrait;

  /**
   * Validates the rules for a domain alias.
   */
  public function validate(DomainAliasInterface $alias) {
    $pattern = $alias->getPattern();

    // 1) Check that the alias only has one wildcard.
    $count = substr_count($pattern, '*') + substr_count($pattern, '?');
    if ($count > 1) {
      return $this->t('You may only have one wildcard character in each alias.');
    }
    // 2) Only one colon allowed, and it must be followed by an integer.
    $count = substr_count($pattern, ':');
    if ($count > 1) {
      return $this->t('You may only have one colon ":" character in each alias.');
    }
    elseif ($count == 1) {
      $int = substr($pattern, strpos($pattern, ':') + 1);
      if (!is_numeric($int)) {
        return $this->t('A colon may only be followed by an integer indicating the proper port.');
      }
    }
    // 3) Check that the alias doesn't contain any invalid characters.
    $check = preg_match('/^[a-z0-9\.\+\-\*\?:]*$/', $pattern);
    if ($check == 0) {
      return $this->t('The pattern contains invalid characters.');
    }
    // 4) Check that the alias is not a direct match for a registered domain.
    $check = preg_match('/[a-z0-9\.\+\-:]*$/', $pattern);
    if ($check == 1 && $test = \Drupal::service('domain.loader')->loadByHostname($pattern)) {
      return $this->t('The pattern matches an existing domain record.');
    }
    // 5) Check that the alias is unique across all records.
    if ($alias_check = \Drupal::service('domain_alias.loader')->loadByPattern($pattern)) {
      if ($alias_check->id() != $alias->id()) {
        return $this->t('The pattern already exists.');
      }
    }
  }

}
