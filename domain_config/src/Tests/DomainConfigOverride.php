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
      'group' => 'Domain Configuration',
    );
  }

  function testDomainConfigOverride() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Test the response of the default user page.
    // If we leave path as /, the test fails?!?
    foreach (domain_load_multiple() as $domain) {
      $path = $domain->path . 'user';
      $this->drupalGet($path);
      if ($domain->is_default) {
        $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
      }
      else {
        $this->assertRaw('<title>Log in | ' . $domain->name . '</title>', 'Loaded the proper site name.');
      }
    }

  }

}
