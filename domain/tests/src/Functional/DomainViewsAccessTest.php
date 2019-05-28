<?php

namespace Drupal\Tests\domain\Functional;

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
  public static $modules = ['domain', 'node', 'views', 'block'];

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    // Create five new domains programmatically.
    $this->domainCreateTestDomains(5);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    // Enable the views.
    $this->enableViewsTestModule();
    // Create a user. To test the area output was more difficult, so we just
    // configured two views. The page shows the first, admin, user, and the
    // block will show this new user name.
    $this->user = $this->drupalCreateUser(['administer domains', 'create domains']);
    // Place the view block.
    $this->drupalPlaceBlock('views_block:domain_views_access-block_1');

    // The block and page should be visible on example_com and one_example_com.
    $allowed = ['example_com', 'one_example_com'];

    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'domain-views-access';
      $this->DrupalGet($path);
      if (in_array($domain->id(), $allowed)) {
        $this->assertResponse('200', 'Access allowed');
        $this->assertRaw('admin');
        $this->assertRaw($this->user->getUsername());
      }
      else {
        $this->assertResponse('403', 'Access denied');
        $this->assertNoRaw('admin');
        $this->assertNoRaw($this->user->getUsername());
      }
      // Test the block on another page.
      $this->drupalGet($domain->getPath());
      if (in_array($domain->id(), $allowed)) {
        $this->assertRaw($this->user->getUsername());
      }
      else {
        $this->assertNoRaw($this->user->getUsername());
      }
    }
  }

  /**
   * Sets up the domain_test module.
   *
   * Because the schema of domain_test.module is dependent on the test
   * using it, it cannot be enabled normally.
   */
  protected function enableViewsTestModule() {
    \Drupal::service('module_installer')->install(['domain_test']);
    $this->resetAll();
    $this->rebuildContainer();
    $this->container->get('module_handler')->reload();
  }

}
