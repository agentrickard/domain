<?php

namespace Drupal\domain_alias;

/**
 * Supplies validator methods for common domain requests.
 */
interface DomainAliasValidatorInterface {

  /**
   * Validates the rules for a domain alias.
   *
   * @param \Drupal\domain_alias\DomainAliasInterface $alias
   *   The domain alias to validate.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The validation error message, if any.
   */
  public function validate(DomainAliasInterface $alias);

}
