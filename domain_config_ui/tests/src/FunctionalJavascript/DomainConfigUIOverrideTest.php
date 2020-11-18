<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the domain config user interface.
 *
 * @group domain_config_ui
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
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_config_ui',
    'domain_config_test',
    'language',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createAdminUser();
    $this->createEditorUser();

    $this->setBaseHostname();
    $this->domainCreateTestDomains(5);

    $this->createLanguage();
  }

  /**
   * Tests that we can save domain and language-specific settings.
   */
  public function testAjax() {
    // Test base configuration.
    $config_name = 'system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals($config['name'], 'Drupal');
    $this->assertEquals($config['page']['front'], '/user/login');

    // Test stored configuration.
    $config_name = 'domain.config.one_example_com.en.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals($config['name'], 'One');
    $this->assertEquals($config['page']['front'], '/node/1');

    $this->drupalLogin($this->adminUser);
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

    // We did not save a language prefix, so none will be present.
    $config_name = 'domain.config.one_example_com.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals($config['name'], 'New name');
    $this->assertEquals($config['page']['front'], '/user');

    // Now let's save a language.
    // Visit the site information page.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();

    // Test our form.
    $page->selectFieldOption('domain', 'one_example_com');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('language', 'es');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->fillField('site_name', 'Neuvo nombre');
    $page->fillField('site_frontpage', '/user');
    $this->htmlOutput($page->getHtml());
    $page->pressButton('Save configuration');
    $this->htmlOutput($page->getHtml());

    // We did save a language prefix, so one will be present.
    $config_name = 'domain.config.one_example_com.es.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals($config['name'], 'Neuvo nombre');
    $this->assertEquals($config['page']['front'], '/user');

    // Make sure the base is untouched.
    $config_name = 'system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals($config['name'], 'Drupal');
    $this->assertEquals($config['page']['front'], '/user/login');
  }

  /**
   * Creates a second language for testing overrides.
   */
  private function createLanguage() {
    // Create and login user.
    $adminUser = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($adminUser);

    // Add language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    $this->drupalLogout();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertTrue(!empty($es), 'Created test language.');
  }

}
