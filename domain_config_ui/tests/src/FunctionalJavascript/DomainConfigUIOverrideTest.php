<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavaScript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\domain\DomainInterface;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the domain config user interface.
 *
 * @group domain_config_ui_js
 */
class DomainConfigUIOverrideTest extends WebDriverTestBase {

  use DomainConfigUITestTrait;
  use DomainTestTrait;

  /**
   * Disabled config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_config_ui',
    'domain_config_test',
    'language'
  ];

  public function setUp() {
    parent::setUp();

    $this->createAdminUser();
    $this->createEditorUser();

    $this->setBaseHostname();
    $this->domainCreateTestDomains(5);

    $this->createLanguage();

  }

  public function testAjax() {
    // This test works fine in DomainConfigOverriderTest.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      foreach (['en', 'es'] as $langcode) {
        $path = $domain->getPath();
        if ($langcode === 'es') {
          $path = $domain->getPath() . $langcode;
        }
        $this->drupalGet($path);
        $this->assertRaw('| ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
      }
    }

    $this->drupalLogin($this->admin_user);
    $path = '/admin/config/system/site-information';

    // Visit the site information page.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();

    // Test our form.
    $page->findField('domain');
    $page->findField('language');
    $page->selectFieldOption('domain', 'one_example_com');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->fillField('site_name', 'New name');
    $page->fillField('site_frontpage', '/user');
    $this->htmlOutput($page->getHtml());
    $page->pressButton('Save configuration');
    $this->htmlOutput($page->getHtml());

    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
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
      foreach (['en', 'es'] as $langcode) {
        $path = $domain->getPath();
        if ($langcode === 'es') {
          $path = $domain->getPath() . $langcode;
        }
        $this->drupalGet($path);
        if ($domain->id() === 'one_example_com') {
          $this->assertRaw('| New name</title>', 'Loaded the proper site name.');
        }
        else {
          $this->assertRaw('| ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
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

  private function createLanguage() {
    // Create and login user.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($admin_user);

    // Add language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->drupalLogout();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertTrue(!empty($es), 'Created test language.');
  }

}
