<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasTestBase.
 */

namespace Drupal\domain_alias\Tests;

use Drupal\domain\Tests\DomainTestBase;

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

  function setUp() {
    parent::setUp();
  }

}
