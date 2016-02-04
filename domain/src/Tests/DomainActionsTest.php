<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainActionsTest.
 */

namespace Drupal\domain\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain\Tests\DomainTestBase;

/**
 * Tests the domain record actions.
 *
 * @group domain
 */
class DomainActionsTest extends DomainTestBase {

  /**
   * Tests bulk actions through the domain overview page.
   */
  function testDomainActions() {
    $this->admin_user = $this->drupalCreateUser(array('administer domains', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    $path = 'admin/config/domain';

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create test domains.
    $this->domainCreateTestDomains(4);

    // Visit the domain overview administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test the domains.
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue(count($domains) == 4, 'Four domain records found.');

    // Check the default domain.
    $default = \Drupal::service('domain.loader')->loadDefaultId();
    $key = 'example_com';
    $this->assertTrue($default == $key, 'Default domain set correctly.');

    // Test some text on the page.
    foreach ($domains as $domain) {
      $name = $domain->label();
      $this->assertText($name, format_string('@name found on overview page.', array('@name' => $name)));
    }
    // @TODO: Test the list of actions.
    $actions = array('delete', 'disable', 'default');
    foreach ($actions as $action) {
      $this->assertRaw("/domain/{$action}/", format_string('@action action found.', array('@action' => $action)));
    }
    // @TODO: Disable a domain and test the enable link.

    // @TODO: test the link behaviors.

    // @TODO: test permissions on actions

  }

}

