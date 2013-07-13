<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasFieldTest
 */

namespace Drupal\domain_alias\Tests;
use Drupal\domain\DomainInterface;
use Drupal\domain_alias\Tests\DomainAliasTestBase;

/**
 * Tests the domain record creation API.
 */
class DomainAliasFieldTest extends DomainAliasTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain alias field tests',
      'description' => 'Attach domain alias fields to domains.',
      'group' => 'Domain Alias',
    );
  }

  function testDomainAliasField() {

  }

}
