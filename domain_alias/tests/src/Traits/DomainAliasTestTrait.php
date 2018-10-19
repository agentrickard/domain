<?php

namespace Drupal\Tests\domain_alias\Traits;

use Drupal\domain\DomainInterface;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait DomainAliasTestTrait {

  /**
   * Creates an alias.
   *
   * @param array $values
   *   An array of values to assign to the alias.
   * @param bool $save
   *   Whether to save the alias or return for validation.
   *
   * @return \Drupal\domain_alias\Entity\DomainAlias
   *   A domain alias entity.
   */
  public function createDomainAlias(array $values, $save = TRUE) {
    // Replicate the logic for creating machine_name patterns.
    // @see ConfigBase::validate()
    $machine_name = mb_strtolower(preg_replace('/[^a-z0-9_]/', '_', $values['pattern']));
    $values['id'] = str_replace(['*', '.', ':'], '_', $machine_name);
    $alias = \Drupal::entityTypeManager()->getStorage('domain_alias')->create($values);
    if ($save) {
      $alias->save();
    }

    return $alias;
  }

  /**
   * Creates an alias for testing without passing values.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain entity.
   * @param string $pattern
   *   An optional alias pattern.
   * @param int $redirect
   *   An optional redirect (301 or 302).
   * @param int $environment
   *   An optional environment string.
   * @param bool $save
   *   Whether to save the alias or return for validation.
   *
   * @return \Drupal\domain_alias\Entity\DomainAlias
   *   A domain alias entity.
   */
  public function domainAliasCreateTestAlias(DomainInterface $domain, $pattern = NULL, $redirect = 0, $environment = 'default', $save = TRUE) {
    if (empty($pattern)) {
      $pattern = '*.' . $domain->getHostname();
    }
    $values = [
      'domain_id' => $domain->id(),
      'pattern' => $pattern,
      'redirect' => $redirect,
      'environment' => $environment,
    ];

    return $this->createDomainAlias($values, $save);
  }

}
