<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record language negotiator.
 *
 * @group domain
 */
class DomainLanguageNegotiatorTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'language',
    'locale',
  ];

  /**
   * Domain list for this test.
   *
   * @var array
   */
  public $domains;

  /**
   * The created admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  public $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Configure 'node' as front page, else the test loads the login form.
    $site_config = $this->config('system.site');
    $site_config->set('page.front', '/node')->save();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(5);
    $this->domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser(['administer languages', 'administer domains', 'access administration pages']);
    $this->drupalLogin($this->adminUser);

    // Add language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][domain]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $this->drupalGet('admin/config/regional/language/detections/domain');
    $edit = [
      'domain_list[example_com][language]' => 'en',
      'domain_list[one_example_com][language]' => 'en',
      'domain_list[two_example_com][language]' => 'es',
      'domain_list[three_example_com][language]' => 'es',
      'domain_list[four_example_com][language]' => 'en',
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalLogout();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertNotEmpty($es, 'Created test language.');
  }

  /**
   * Test domain-based language interface.
   */
  public function testDomainLanguageNegotiator() {
    $this->drupalLogin($this->adminUser);

    $expected = $this->getExpectedText();

    // Test the test on the page, which should translate.
    foreach ($this->domains as $domain) {
      $admin_page = $domain->getPath() . 'admin/config/regional/language';
      $this->drupalGet($admin_page);
      $this->assertSession()->pageTextContains($expected[$domain->id()]);
    }
  }

  /**
   * Gets the expected text string on each page.
   *
   * @return array
   *   The array of text, keyed by domain id.
   */
  public function getExpectedText() {
    return [
      'example_com' => 'Default',
      'one_example_com' => 'Default',
      'two_example_com' => 'Por defecto',
      'three_example_com' => 'Por defecto',
      'four_example_com' => 'Default',
    ];
  }
}
