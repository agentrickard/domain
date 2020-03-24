<?php

namespace Drupal\Tests\domain_config_ui\Functional;

use Drupal\Tests\domain_config\Functional\DomainConfigTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\domain\DomainInterface;

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
    // Get the domain list.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    // Test a site name value.
    foreach ($domains as $domain) {
      // No site names have been changed.
      $langcode = 'es';
      $path = $domain->getPath() . $langcode . '/user/login';
      $this->drupalGet($path);
      $this->assertRaw('<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
      $langcode = '';
      $path = $domain->getPath() . '/user/login';
      $this->drupalGet($path);
      $this->assertRaw('<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
    }

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


    // Except for the default domain, the page title element should match what
    // is in the override files.
    // With a language context, based on how we have our files setup, we
    // expect the following outcomes:
    // - example.com name = 'Drupal' for English, 'Drupal' for Spanish.
    // - one.example.com name = 'One' for English, 'Drupal' for Spanish.
    // - two.example.com name = 'Two' for English, 'Dos' for Spanish.
    // - three.example.com name = 'Drupal' for English, 'Drupal' for Spanish.
    // - four.example.com name = 'Four' for English, 'Four' for Spanish.
    foreach ($domains as $domain) {
      // Test the login page, because our default homepages do not exist.
      foreach (['en', 'es'] as $langcode) {
        $path = $domain->getPath() . $langcode . '/user/login';
        $this->drupalGet($path);
        if ($domain->isDefault()) {
        #  $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
        }
        else {
        #  $this->assertRaw('<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
        }
      }
    }
  }

  /**
   * Returns the expected site name value from our test configuration.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The Domain object.
   * @param string $langcode
   *   A two-digit language code.
   *
   * @return string
   *   The expected name.
   */
  private function expectedName(DomainInterface $domain, $langcode = NULL) {
    $name = '';

    switch ($domain->id()) {
      case 'example_com':
        $name = 'Drupal';
        break;

      case 'one_example_com':
        $name = ($langcode == 'es') ? 'Drupal' : 'One';
        break;

      case 'two_example_com':
        $name = ($langcode == 'es') ? 'Dos' : 'Two';
        break;

      case 'three_example_com':
        $name = 'Drupal';
        break;

      case 'four_example_com':
        $name = 'Four';
        break;
    }

    return $name;
  }

}
