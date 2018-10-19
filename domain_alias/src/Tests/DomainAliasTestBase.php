<?php

namespace Drupal\domain_alias\Tests;

use Drupal\domain\Tests\DomainTestBase;
use Drupal\Tests\domain_alias\Traits\DomainAliasTestTrait;

/**
 * Base class and helper methods for testing domain aliases.
 *
 * @deprecated
 *  This class will be removed before the 8.1.0 release.
 *  Use DomainStorage instead, loaded through the EntityTypeManager.
 */
abstract class DomainAliasTestBase extends DomainTestBase {

  use DomainAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_alias'];

}
