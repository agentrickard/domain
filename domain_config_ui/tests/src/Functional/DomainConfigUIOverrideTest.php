<?php

namespace Drupal\Tests\domain_config_ui\Functional;

use Drupal\Tests\domain_config\Functional\DomainConfigTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;

/**
 * Tests the domain config user interface.
 *
 * @group domain_config_ui
 */
class DomainConfigUIOverrideTest extends DomainConfigTestBase {

  use DomainConfigUITestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_config_ui',
  ];

  public function setUp() {
    parent::setUp();

    $this->createAdminUser();
    $this->createEditorUser();

    $this->domainCreateTestDomains(5);
  }


  /**
   * Tests access the the settings form.
   */
  public function testDomainConfigUISettingsAccess() {
    // Test a site name value.

    $this->drupalLogin($this->admin_user);
    $path = '/admin/config/domain/config-ui';
    $path2 = '/admin/config/system/site-information';

    // Visit the domain config ui administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Visit the site information page.
    $this->drupalGet($path2);
    $this->assertResponse(200);
    $this->findField('domain');

    // Save and test a change.

  }

}
