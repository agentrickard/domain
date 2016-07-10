<?php

namespace Drupal\domain_alias\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain\Tests\DomainTestBase;

/**
 * Base class and helper methods for testing domain aliases.
 */
abstract class DomainAliasTestBase extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_alias');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Creates an alias for testing.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain entity.
   * @param string $pattern
   *   An optional alias pattern.
   * @param int $redirect
   *   An optional redirect (301 or 302).
   * @param boolean $save
   *   Whether to save the alias or return for validation.
   *
   * @return \Drupal\domain_alias\Entity\DomainAlias
   *   A domain alias entity.
   */
  public function domainAliasCreateTestAlias(DomainInterface $domain, $pattern = NULL, $redirect = 0, $save = TRUE) {
    if (empty($pattern)) {
      $pattern = '*.' . $domain->getHostname();
    }
    $values = array(
      'domain_id' => $domain->id(),
      'pattern' => $pattern,
      'redirect' => $redirect,
    );
    // Replicate the logic for creating machine_name patterns.
    // @see ConfigBase::validate()
    $machine_name = strtolower(preg_replace('/[^a-z0-9_]/', '_', $values['pattern']));
    $values['id'] = str_replace(array('*', '.', ':'), '_', $machine_name);
    $alias = \Drupal::entityTypeManager()->getStorage('domain_alias')->create($values);
    if ($save) {
      $alias->save();
    }

    return $alias;
  }

}
