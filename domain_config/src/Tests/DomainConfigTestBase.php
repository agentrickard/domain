<?php

/**
 * @file
 * Definition of Drupal\domain_config\Tests\DomainConfigTestBase.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain\Tests\DomainTestBase;

/**
 * Helper test methods for Domain Config testing.
 */
abstract class DomainConfigTestBase extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_config', 'domain_config_test');

  function setUp() {
    parent::setUp();
  }

}
