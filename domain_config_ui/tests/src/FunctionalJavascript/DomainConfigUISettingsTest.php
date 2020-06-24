<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\Tests\domain\Traits\DomainTestTrait;
use Drupal\domain_config_ui\DomainConfigUITrait;

/**
 * Tests the domain config settings interface.
 *
 * @group domain_config_ui
 */
class DomainConfigUISettingsTest extends WebDriverTestBase {

  use DomainConfigUITrait;
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

  }

  /**
   * Tests ability to add/remove forms.
   */
  public function testSettings() {
    $config = $this->config('domain_config_ui.settings');
    $expected = $this->explodePathSettings("/admin/appearance\r\n/admin/config/system/site-information");
    $value = $this->explodePathSettings($config->get('path_pages'));
    $this->assertEquals($expected, $value);

    $this->drupalLogin($this->adminUser);

    // Test some theme paths.
    $path = '/admin/appearance';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Disable domain configuration');

    $path = '/admin/appearance/settings/stark';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Enable domain configuration');
    $page->clickLink('Enable domain configuration');

    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalGet($path);
    $config2 = $this->config('domain_config_ui.settings');
    $expected2 = $this->explodePathSettings("/admin/appearance\r\n/admin/config/system/site-information\r\n/admin/appearance/settings/stark");
    $value2 = $this->explodePathSettings($config2->get('path_pages'));
    $this->assertEquals($expected2, $value2);

    // Test removal of paths.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Disable domain configuration');
    $page->clickLink('Disable domain configuration');

    $this->assertSession()->assertWaitOnAjaxRequest();

    $path = '/admin/config/system/site-information';
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Disable domain configuration');
    $page->clickLink('Disable domain configuration');

    $this->assertSession()->assertWaitOnAjaxRequest();

    $expected3 = $this->explodePathSettings("/admin/appearance");
    $config3 = $this->config('domain_config_ui.settings');
    $value3 = $this->explodePathSettings($config3->get('path_pages'));
    $this->assertEquals($expected3, $value3);

    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Enable domain configuration');

    // Ensure the editor cannot access the form.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains('Enable domain configuration');
  }

}
