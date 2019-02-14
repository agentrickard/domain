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

  protected $admin_user;

  protected $editor_user;

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

    // Create users.
    $this->admin_user = $this->drupalCreateUser([
      'administer domains',
      'administer domain config ui',
      'use domain config ui',
      'administer languages',
      'access administration pages',
      'administer site configuration',
    ]);

    // Create users.
    $this->editor_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
    ]);

    $this->domainCreateTestDomains(5);
    // Assign the admin_user to some domains.
    // $entity_type, $entity_id, $ids, $field
    $this->addDomainsToEntity('user', $this->admin_user->id(), ['example_com', 'one_example_com'], DOMAIN_ADMIN_FIELD);
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
    $this->assertEqual($expected, $value);

    $this->drupalLogin($this->admin_user);
    $path = '/admin/config/system/site-information';
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findLink('Disable domain configuration');
    $this->clickLink('Disable domain configuration');

    $expected = "/admin/appearance";
    $config = $this->config('domain_config_ui.settings');
    $value = $config->get('path_pages');
    $this->assertEqual($expected, $value);

    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findLink('Enable domain configuration');

    $this->drupalLogin($this->editor_user);
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->findNoLink('Enable domain configuration');
  }

}
