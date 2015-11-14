<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainConfigOverriderTest.
 */

namespace Drupal\domain_config\Tests;

/**
 * Tests the domain config system.
 *
 * @group domain_config
 */
class DomainConfigOverriderTest extends DomainConfigTestBase {

  /**
   * Disabled config schema checking because Domain Config actually duplicates
   * schemas provided by other modules, so cannot define its own.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests that domain-specific variable loading works.
   */
  function testDomainConfigOverrider() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    $node = $this->drupalCreateNode(array(
      'type' => 'article',
    ));

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);
    // Test the response of the default user page.
    // If we leave path as /, the test fails?!?
    foreach (\Drupal::service('domain.loader')->loadMultiple() as $domain) {
      $path = $domain->getPath() . 'node/' . $node->id();
      $this->drupalGet($path);
      if ($domain->isDefault()) {
        $this->assertRaw('<title>' . $node->getTitle() . ' | Drupal</title>', 'Loaded the proper site name.');
      }
      else {
        $this->assertRaw('<title>' . $node->getTitle() . ' | ' . $domain->label() . '</title>', 'Loaded the proper site name.');
      }
    }

  }

}
