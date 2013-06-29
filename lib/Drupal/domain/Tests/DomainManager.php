<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainManager
 */

namespace Drupal\domain\Tests;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record creation API.
 */
class DomainManager extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain manager tests',
      'description' => 'Tests domain response management.',
      'group' => 'Domain',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test');

  function testDomainManager() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Test the response of the default home page.
    foreach (domain_load_multiple() as $domain) {
      $this->drupalGet($domain->path);
      // This call doesn't persist, so the tests won't work.
      # $active = Drupal::service('domain.manager');
      # debug($active);
      // We need to load a block with text instead.
    }
  }

}
