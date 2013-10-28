<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainConfigOverride.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain_config\EventSubscriber\DomainConfigSubscriber;

/**
 * Tests the domain record creation API.
 */
class DomainConfigOverride extends DomainConfigTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain configuration overrides',
      'description' => 'Set domain-specific variables.',
      'group' => 'Domain config',
    );
  }

  function testDomainConfigOverride() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    config_install_default_config('module', 'domain_config_test');

    // Test the response of the default home page.
    foreach (domain_load_multiple() as $domain) {
      $this->drupalGet($domain->path);
      if ($domain->is_default) {
        $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
      }
      else {
        $this->assertRaw('<title>Log in | ' . $domain->name . '</title>', 'Loaded the proper site name.');
      }
    }

  }

}
