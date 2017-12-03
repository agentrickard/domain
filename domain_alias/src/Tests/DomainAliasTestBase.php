<?php

namespace Drupal\domain_alias\Tests;

use Drupal\domain\Tests\DomainTestBase;
use Drupal\Tests\domain_alias\Functional\DomainAliasTestTrait;

/**
 * Base class and helper methods for testing domain aliases.
 */
abstract class DomainAliasTestBase extends DomainTestBase {

  use DomainAliasTestTrait;

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

}
