<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record actions.
 *
 * @group domain
 */
class DomainActionsTest extends DomainTestBase {

  /**
   * Tests bulk actions through the domain overview page.
   */
  public function testDomainActions() {
    $this->admin_user = $this->drupalCreateUser(['administer domains', 'access administration pages']);
    $this->drupalLogin($this->admin_user);

    $path = 'admin/config/domain';

    // Create test domains.
    $this->domainCreateTestDomains(4);

    // Visit the domain overview administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test the domains.
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    $domains = $storage->loadMultiple();
    $this->assertTrue(count($domains) == 4, 'Four domain records found.');

    // Check the default domain.
    $default = $storage->loadDefaultId();
    $key = 'example_com';
    $this->assertTrue($default == $key, 'Default domain set correctly.');

    // Test some text on the page.
    foreach ($domains as $domain) {
      $name = $domain->label();
      $this->assertText($name, 'Name found properly.');
    }
    // Test the list of actions.
    $actions = ['delete', 'disable', 'default'];
    foreach ($actions as $action) {
      $this->assertRaw("/domain/{$action}/", 'Actions found properly.');
    }
    // Check that all domains are active.
    $this->assertNoRaw('Inactive', 'Inactive domain not found.');

    // Disable a domain and test the enable link.
    $this->clickLink('Disable', 0);
    $this->assertRaw('Inactive', 'Inactive domain found.');

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertResponse(200);

    foreach ($storage->loadMultiple() as $domain) {
      if ($domain->id() == 'one_example_com') {
        $this->assertEmpty($domain->status(), 'One domain inactive.');
      }
      else {
        $this->assertNotEmpty($domain->status(), 'Other domains active.');
      }
    }

    // Test the list of actions.
    $actions = ['enable', 'delete', 'disable', 'default'];
    foreach ($actions as $action) {
      $this->assertRaw("/domain/{$action}/", 'Actions found properly.');
    }
    // Re-enable the domain.
    $this->clickLink('Enable', 0);
    $this->assertNoRaw('Inactive', 'Inactive domain not found.');

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertResponse(200);

    foreach ($storage->loadMultiple() as $domain) {
      $this->assertNotEmpty($domain->status(), 'All domains active.');
    }

    // Set a new default domain.
    $this->clickLink('Make default', 0);

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Check the default domain.
    $storage->resetCache();
    $default = $storage->loadDefaultId();
    $key = 'one_example_com';
    $this->assertTrue($default == $key, 'Default domain set correctly.');

  }

}
