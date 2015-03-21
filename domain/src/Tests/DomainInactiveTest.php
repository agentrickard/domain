<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainInactiveTest.
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the redirects for inactive domains.
 *
 * @group domain
 */
class DomainInactiveTest extends DomainTestBase {

  public function testInactiveDomain() {
    // Create three new domains programmatically.
    $this->domainCreateTestDomains(3);
    $domains = domain_load_multiple();
    // Grab the last domain for testing/
    $domain = end($domains);
    $this->drupalGet($domain->getPath());
    $this->assertRaw($domain->getPath(), 'Loaded the active domain.');
    $this->assertTrue($domain->status(), 'Tested domain is set to active.');
    // Disable the domain and test for redirect.
    $domain->disable();
    $default = domain_default();
    $this->drupalGet($domain->getPath());
    $this->assertRaw($default->getPath(), 'Redirected an inactive domain to the default domain.');
    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    // Try to access with the proper permission.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access inactive domains'));
    $this->drupalGet($domain->getPath());
    $this->assertRaw($domain->getPath(), 'Loaded the inactive domain with permission.');
    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
  }

}
