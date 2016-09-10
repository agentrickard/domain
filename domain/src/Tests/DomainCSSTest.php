<?php

namespace Drupal\domain\Tests;

/**
 * Tests the domain CSS configuration.
 *
 * @group domain
 */
class DomainCSSTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain');

  /**
   * Tests the handling of an inbound request.
   */
  public function testDomainNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Test the response of the default home page.
    foreach (\Drupal::service('domain.loader')->loadMultiple() as $domain) {
      $this->drupalGet($domain->getPath());
      $text = '<body class="' . $domain->machine_name . '"';
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
      $this->assertNoRaw($text, 'No custom CSS present.');
    }
  }

}
