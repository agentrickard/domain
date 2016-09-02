<?php

namespace Drupal\domain\Tests;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests the domain access plugin for Views.
 *
 * @group domain
 */
class DomainViewsAccessTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node', 'views', 'domain_test');

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    // Create five new domains programmatically.
    $this->domainCreateTestDomains(5);
    $domains = \Drupal::service('domain.loader')->loadMultiple();

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('views_block__domain_views_access_block_1');

    // The block and page should be visible on example_com and one_example_com.
    $allowed = ['example_com', 'one_example_com'];

    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'domain-views-access';
      $this->DrupalGet($path);
      if (in_array($domain->id(), $allowed)) {
        $this->assertResponse('200', 'Access allowed');
        $this->assertRaw('Test page output.');
        $this->assertRaw('Test block output.');
      }
      else {
        $this->assertResponse('403', 'Access denied');
        $this->assertNoRaw('Test page output.');
        $this->assertNoRaw('Test block output.');
      }
    }
  }

}
