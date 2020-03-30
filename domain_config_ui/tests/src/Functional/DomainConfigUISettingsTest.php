<?php

namespace Drupal\Tests\domain_config_ui\Functional;

use Drupal\Tests\domain_config\Functional\DomainConfigTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;

/**
 * Tests the domain config user interface.
 *
 * @group domain_config_ui
 */
class DomainConfigUISettingsTest extends DomainConfigTestBase {

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

    $this->drupalLogin($this->editor_user);

    // Visit the domain config ui administration page.
    $this->drupalGet($path);
    $this->assertResponse(403);

    // Visit the site information page.
    $this->drupalGet($path2);
    $this->assertResponse(200);
    $this->findNoField('domain');
  }

  /**
   * Tests ability to add/remove forms.
   */
  public function testDomainConfigUIAddForm() {
    $config = $this->config('domain_config_ui.settings');
    $expected = "/admin/appearance\r\n/admin/config/system/site-information";
    $value = $config->get('path_pages');
    $this->assertEquals($expected, $value);

    $this->drupalLogin($this->admin_user);
    $path = '/admin/config/system/site-information';
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findLink('Disable domain configuration');
    $this->clickLink('Disable domain configuration');

    $this->container
      ->get('config.factory')->clearStaticCache();

    $expected2 = "/admin/appearance";
    $config = $this->config('domain_config_ui.settings');
    $value2 = $config->get('path_pages');
    $this->assertEquals($expected2, $value2);

    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findLink('Enable domain configuration');

    $this->drupalLogin($this->editor_user);
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findNoLink('Enable domain configuration');
  }

}
