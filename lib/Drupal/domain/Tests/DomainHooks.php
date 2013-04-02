<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainHooks
 */

namespace Drupal\domain\Tests;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record creation API.
 */
class DomainHooks extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain module hooks',
      'description' => 'Tests domain module API hooks.',
      'group' => 'Domain',
    );
  }

  /**
   * Test domain loading.
   */
  function testHookDomainLoad() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a domain.
    $this->domainCreateTestDomains();

    $domain = domain_load(1);

    $this->assertTrue(isset($domain->foo), 'The foo value was set by hook_domain_load.');
  }

}
