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
  public static $modules = ['domain', 'node', 'language', 'views'];

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

    $this->drupalGet('admin/config/regional/language/detection/domain');
    #$this->submitForm($edit, 'Save configuration');

    $this->drupalLogout();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertNotEmpty($es, 'Created test language.');

  }

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    $domains = $this->domains;

    // Grab a known domain for testing.
    $domain = $domains['two_example_com'];
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain->status(), 'Tested domain is set to active.');
    $this->assertTrue($domain->getPath() == $this->getUrl(), 'Loaded the active domain.');
  }

}
