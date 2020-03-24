<?php

namespace Drupal\Tests\domain_config_ui\Functional;

use Drupal\Tests\domain_config\Functional\DomainConfigTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;

/**
 * Tests granular permissions for the domain config user interface.
 *
 * @group domain_config_ui
 */
class DomainConfigUIOptionsTest extends DomainConfigTestBase {

  use DomainConfigUITestTrait;

  /**
   *  @var Drupal\Core\Session\AccountInterface
   *  A user with full permissions to use the module.
   */
  protected $admin_user;

  /**
   *  @var Drupal\Core\Session\AccountInterface
   *  A user with access to domains but not language.
   */
  protected $editor_user;

  /**
   *  @var Drupal\Core\Session\AccountInterface
   *  A user with permission to domains and language.
   */
  protected $language_user;

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
      'translate domain configuration',
      'administer languages',
      'access administration pages',
      'administer site configuration',
    ]);

    // Create users.
    $this->editor_user = $this->drupalCreateUser([
      'access administration pages',
      'use domain config ui',
      'administer site configuration',
    ]);

    // Create users.
    $this->language_user = $this->drupalCreateUser([
      'access administration pages',
      'use domain config ui',
      'translate domain configuration',
      'administer site configuration',
    ]);

    $this->domainCreateTestDomains(5);
    // Assign the admin_user and editor_user to some domains.
    // $entity_type, $entity_id, $ids, $field
    $this->addDomainsToEntity('user', $this->editor_user->id(), ['example_com', 'one_example_com'], DOMAIN_ADMIN_FIELD);
    $this->addDomainsToEntity('user', $this->language_user->id(), ['two_example_com', 'three_example_com'], DOMAIN_ADMIN_FIELD);
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
    $this->findField('language');

    // We expect to find five domain options.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      $this->assertRaw($string, 'Found the domain option.');
    }
    // We expect to find two language options.
    $languages = ['en', 'es'];
    foreach ($languages as $langcode) {
      $string = 'value="' . $langcode . '"';
      $this->assertRaw($string, 'Found the language option.');
    }

    // Now test the editor_user.
    $this->drupalLogin($this->editor_user);

    // Visit the domain config ui administration page.
    $this->drupalGet($path);
    $this->assertResponse(403);

    // Visit the site information page.
    $this->drupalGet($path2);
    $this->assertResponse(200);
    $this->findField('domain');
    $this->findNoField('language');

    // We expect to find two domain options.
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      if (in_array($domain->id(), ['example_com', 'one_example_com'], TRUE)) {
        $this->assertRaw($string, 'Found the domain option.');
      }
      else {
        $this->assertNoRaw($string, 'Did not find the domain option.');
      }
    }

    // Now test the language_user.
    $this->drupalLogin($this->language_user);

    // Visit the domain config ui administration page.
    $this->drupalGet($path);
    $this->assertResponse(403);

    // Visit the site information page.
    $this->drupalGet($path2);
    $this->assertResponse(200);
    $this->findField('domain');
    $this->findField('language');

    // We expect to find two domain options.
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      if (in_array($domain->id(), ['two_example_com', 'three_example_com'], TRUE)) {
        $this->assertRaw($string, 'Found the domain option.');
      }
      else {
        $this->assertNoRaw($string, 'Did not find the domain option.');
      }
    }
    // We expect to find two language options.
    $languages = ['en', 'es'];
    foreach ($languages as $langcode) {
      $string = 'value="' . $langcode . '"';
      $this->assertRaw($string, 'Found the language option.');
    }

  }

}
