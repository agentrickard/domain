<?php

namespace Drupal\domain\Tests;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests the access rules and redirects for inactive domains.
 *
 * @group domain
 */
class DomainInactiveTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node', 'views');

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    // Configure 'node' as front page, else the test loads the login form.
    $site_config = $this->config('system.site');
    $site_config->set('page.front', '/node')->save();

    // Create three new domains programmatically.
    $this->domainCreateTestDomains(3);
    $domains = \Drupal::service('domain.loader')->loadMultiple();

    // Grab the last domain for testing.
    $domain = end($domains);
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain->status(), 'Tested domain is set to active.');
    $this->assertTrue($domain->getPath() == $this->getUrl(), 'Loaded the active domain.');

    // Disable the domain and test for redirect.
    $domain->disable();
    $default = \Drupal::service('domain.loader')->loadDefaultDomain();
    // Our postSave() cache tag clear should allow this to work properly.
    $this->drupalGet($domain->getPath());

    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    $this->assertTrue($default->getPath() == $this->getUrl(), 'Redirected an inactive domain to the default domain.');

    // Check to see if the user can login.
    $url = $domain->getPath() . 'user/login';
    $this->drupalGet($url);
    $this->assertResponse(200, 'Request to login on inactive domain allowed.');
    // Check to see if the user can reset password.
    $url = $domain->getPath() . 'user/password';
    $this->drupalGet($url);
    $this->assertResponse(200, 'Request to reset password on inactive domain allowed.');

    // @TODO: configure more paths and test.

    // Try to access with the proper permission.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, array('access inactive domains'));
    // Must flush cache because we did not resave the domain.
    drupal_flush_all_caches();
    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain->getPath() == $this->getUrl(), 'Loaded the inactive domain with permission.');
  }

}
