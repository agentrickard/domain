<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasTestBase.
 */

namespace Drupal\domain_alias\Tests;

use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain alias interface.
 */
abstract class DomainAliasTestBase extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_alias');

  public function setUp() {
    parent::setUp();
  }

  /**
   * Creates an alias for testing.
   *
   * @param Drupal\domain\Entity\Domain $domain
   *   A domain entity.
   * @param string $pattern
   *   An optional alias pattern.
   * @param int $redirect
   *   An optional redirect (301 or 302).
   *
   * @return Drupal\domain_alias\Entity\DomainAlias
   *   A domain alias entity.
   */
  public function domainAliasCreateTestAlias(DomainInterface $domain, $pattern = NULL, $redirect = 0) {
    if (empty($pattern)) {
      $pattern = '*.' . $domain->getHostname();
    }
    $values = array(
      'domain_id' => $domain->id(),
      'pattern' => $pattern,
      'redirect' => $redirect,
    );
    $values['id'] = str_replace(array('*', '.'), '_', $values['pattern']);
    $alias = \Drupal::entityManager()->getStorage('domain_alias')->create($values);
    // @TODO: test this logic.
    $alias->save();
    return $alias;
  }

}
