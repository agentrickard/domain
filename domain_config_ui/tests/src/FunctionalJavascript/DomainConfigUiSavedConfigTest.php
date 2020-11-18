<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the domain config inspector.
 *
 * @group domain_config_ui
 */
class DomainConfigUiSavedConfigTest extends WebDriverTestBase {

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
  public function testSavedConfig() {
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

    // Now, head to /admin/config/domain/config-ui/list.
    $path = '/admin/config/domain/config-ui/list';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $this->htmlOutput($page->getHtml());
    $this->assertSession()->pageTextContains('Saved configuration');
    $this->assertSession()->pageTextContains('domain.config.one_example_com.system.site');
    $this->assertSession()->pageTextContains('domain.config.one_example_com.es.system.site');
    $this->assertSession()->pageTextNotContains('domain.config.example_com.en.system.site');

    $page->findLink('Inspect');
    $page->clickLink('Inspect');
    $page = $this->getSession()->getPage();
    $this->htmlOutput($page->getHtml());
    $this->assertSession()->pageTextContains('domain.config.one_example_com.es.system.site');
    $this->assertSession()->pageTextContains('Neuvo nombre');

    $path = '/admin/config/domain/config_ui/inspect/domain.config.one_example_com.system.site';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $this->htmlOutput($page->getHtml());
    $this->assertSession()->pageTextContains('domain.config.one_example_com.system.site');
    $this->assertSession()->pageTextContains('New name');

    $path = '/admin/config/domain/config_ui/delete/domain.config.one_example_com.system.site';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $this->htmlOutput($page->getHtml());
    $this->assertSession()->pageTextContains('Are you sure you want to delete the configuration override: domain.config.one_example_com.system.site?');
    $page->findButton('Delete configuration');
    $page->pressButton('Delete configuration');

    // Now, head to /admin/config/domain/config-ui/list.
    $path = '/admin/config/domain/config-ui/list';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $this->htmlOutput($page->getHtml());
    $this->assertSession()->pageTextContains('Saved configuration');
    $this->assertSession()->pageTextNotContains('domain.config.one_example_com.system.site');
    $this->assertSession()->pageTextContains('domain.config.one_example_com.es.system.site');
    $this->assertSession()->pageTextNotContains('domain.config.example_com.en.system.site');

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
