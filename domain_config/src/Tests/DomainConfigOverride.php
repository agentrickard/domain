<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainConfigOverride.
 */

namespace Drupal\domain_config\Tests;

/**
 * Tests the domain config system.
 *
 * @group domain_config
 */
class DomainConfigOverride extends DomainConfigTestBase {

  /**
   * Tests that domain-specific variable loading works.
   */
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
