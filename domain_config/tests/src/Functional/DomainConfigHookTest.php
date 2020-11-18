<?php

namespace Drupal\Tests\domain_config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the domain config system.
 *
 * @group domain_config
 */
class DomainConfigHookTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'domain',
    // See module info file for description of what this module does.
    'domain_config_hook_test',
  ];

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * Test to ensure a domain_config_hook_test_user_login() does not run.
   *
   * This test serves as a control to show the domain_config_hook_test module
   * functions correctly on it's own. Only when you add the domain_config
   * module, does it fail.
   */
  public function testHookRuns() {
    $this->drupalGet('user/login');
    $user = $this->drupalCreateUser([]);
    $edit = ['name' => $user->getAccountName(), 'pass' => $user->passRaw];
    $this->submitForm($edit, 'Log in');

    $test = \Drupal::state()->get('domain_config_test__user_login', NULL);
    // When this test passes, it means domain_config_hook_test_user_login was
    // not run.
    $this->assertNull($test, 'The hook_user_login state message is set.');
  }

}
