<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavaScript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain_config\Functional\DomainConfigTestBase;
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
    'language',
  ];

  public function setUp() {
    parent::setUp();

    $this->createAdminUser();
    $this->createEditorUser();

    #$this->createTestDomains(5);
  }

  public function testAjax() {
    $this->drupalLogin($this->admin_user);
    $path = '/admin/config/system/site-information';
    // Visit the site information page.
    $this->drupalGet($path);
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
