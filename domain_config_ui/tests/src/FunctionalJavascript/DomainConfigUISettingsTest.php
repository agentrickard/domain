<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the domain config settings interface
 *
 * @group domain_config_ui
 */
class DomainConfigUISettingsTest extends WebDriverTestBase {

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
    'language'
  ];

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
  public function testDomainConfigUISettings() {
    $config = $this->config('domain_config_ui.settings');
    $expected = "/admin/appearance\r\n/admin/config/system/site-information";
    $value = $config->get('path_pages');
    $this->assertEquals($expected, $value);

    $this->drupalLogin($this->admin_user);

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

    $config2 = $this->config('domain_config_ui.settings');
    $expected2 = "/admin/appearance\r\n/admin/config/system/site-information\r\n/admin/appearance/settings/stark";
    $value2 = $config2->get('path_pages');
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

    $expected3 = "/admin/appearance";
    $config3 = $this->config('domain_config_ui.settings');
    $value3 = $config3->get('path_pages');
    $this->assertEquals($expected3, $value3);

    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->findLink('Enable domain configuration');

    // Ensure the editor cannot access the form.
    $this->drupalLogin($this->editor_user);
    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains('Enable domain configuration');
  }

}
