<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainActionsTest.
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain record actions.
 *
 * @group domain
 */
class DomainActionsTest extends DomainTestBase {

  /**
   * Tests bulk actions through the Views module.
   */
  function testDomainActions() {
    $this->admin_user = $this->drupalCreateUser(array('administer domains', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    $path = 'admin/structure/domain';

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create test domains.
    $this->domainCreateTestDomains(4);

    // Visit the domains views administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test the domains.
    $domains = domain_load_multiple(NULL, TRUE);
    $this->assertTrue(count($domains) == 4, 'Four domain records found.');

    // Check the default domain.
    $default = domain_default_id();
    // @TODO: We need a new loader?
    $key = domain_machine_name(domain_hostname());
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
    // @TODO Disable a domain and test the enable link.

    // @TODO test the link behaviors.

  }

}

