<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

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
    $this->admin_user = $this->drupalCreateUser(array('administer domains', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    $path = 'admin/config/domain';

    // Create test domains.
    $this->domainCreateTestDomains(4);

    // Visit the domain overview administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test the domains.
    $storage = \Drupal::service('entity_type.manager')->getStorage('domain');
    $domains = $storage->loadMultiple(NULL, TRUE);
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
    // @TODO: Test the list of actions.
    $actions = array('delete', 'disable', 'default');
    foreach ($actions as $action) {
      $this->assertRaw("/domain/{$action}/", 'Actions found properly.');
    }
    // @TODO: Disable a domain and test the enable link.

    // @TODO: test the link behaviors.

    // @TODO: test permissions on actions

  }

}

