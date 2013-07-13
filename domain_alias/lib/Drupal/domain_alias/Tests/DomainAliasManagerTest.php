<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasManagerTest.
 */

namespace Drupal\domain_alias\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain_alias\Tests\DomainAliasTestBase;

/**
 * Tests the domain record creation API.
 */
class DomainAliasManagerTest extends DomainAliasTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain alias manager tests',
      'description' => 'Tests domain alias response management.',
      'group' => 'Domain Alias',
    );
  }

  function testDomainAliasManager() {

  }

}
