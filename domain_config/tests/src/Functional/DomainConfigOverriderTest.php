<?php

namespace Drupal\Tests\domain_config\Functional;

use Drupal\domain\DomainInterface;

/**
 * Tests the domain config system.
 *
 * @group domain_config
 */
class DomainConfigOverriderTest extends DomainConfigTestBase {

  /**
   * Tests that domain-specific variable loading works.
   */
  public function testDomainConfigOverrider() {
    // No domains should exist.
    $this->domainTableIsEmpty();
    // Create five new domains programmatically.
    $this->domainCreateTestDomains(5);
    // Get the domain list.
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
      // Test the login page, because our default homepages do not exist.
      foreach ($this->langcodes as $langcode => $language) {
        $path = $domain->getPath() . $langcode . '/user/login';
        $this->drupalGet($path);
        if ($domain->isDefault()) {
          $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
        }
        else {
          $this->assertRaw('<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>', 'Loaded the proper site name.' . '<title>Log in | ' . $this->expectedName($domain, $langcode) . '</title>');
        }
      }
    }
  }

  /**
   * Tests that domain-specific variable overrides in settings.php works.
   */
  public function testDomainConfigOverriderFromSettings() {
    // Set up overrides.
    $settings = [];
    $settings['config']['domain.config.one_example_com.en.system.site']['name'] = (object) [
      'value' => 'First',
      'required' => TRUE,
    ];
    $settings['config']['domain.config.four_example_com.system.site']['name'] = (object) [
      'value' => 'Four overridden in settings',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Create five new domains programmatically.
    $this->domainCreateTestDomains(5);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(['one_example_com', 'four_example_com']);

    $domain_one = $domains['one_example_com'];
    $this->drupalGet($domain_one->getPath() . 'user/login');
    $this->assertRaw('<title>Log in | First</title>', 'Found overridden slogan for one.example.com.');

    $domain_four = $domains['four_example_com'];
    $this->drupalGet($domain_four->getPath() . 'user/login');
    $this->assertRaw('<title>Log in | Four overridden in settings</title>', 'Found overridden slogan for four.example.com.');
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
