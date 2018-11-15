<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;

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
  public static $modules = ['domain', 'node', 'views'];

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    // Configure 'node' as front page, else the test loads the login form.
    $site_config = $this->config('system.site');
    $site_config->set('page.front', '/node')->save();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    // Grab a known domain for testing.
    $domain = $domains['two_example_com'];
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain->status(), 'Tested domain is set to active.');
    $this->assertTrue($domain->getPath() == $this->getUrl(), 'Loaded the active domain.');

    // Disable the domain and test for redirect.
    $domain->disable();
    $default = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
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

    // Try to access with the proper permission.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access inactive domains']);
    // Must flush cache because we did not resave the domain.
    drupal_flush_all_caches();
    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    $this->drupalGet($domain->getPath());

    // Set up two additional domains.
    $domain2 = $domains['one_example_com'];
    $domain3 = $domains['three_example_com'];

    // Check against trusted host patterns.
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . $this->prepareTrustedHostname($domain->getHostname()) . '$',
        '^' . $this->prepareTrustedHostname($domain2->getHostname()) . '$',
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Revoke the permission change.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access inactive domains']);

    $domain2->saveDefault();

    // Test the trusted host, which should redirect to default.
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain2->getPath() == $this->getUrl(), 'Redirected from the inactive domain.');
    $this->assertResponse(200, 'Request to trusted host allowed.');

    // The redirect is cached, so we must flush cache to test again.
    drupal_flush_all_caches();

    // Test another inactive domain that is not trusted.
    // Disable the domain and test for redirect.
    $domain3->saveDefault();
    $this->drupalGet($domain->getPath());
    $this->assertRaw('The provided host name is not a valid redirect.');
  }

}
