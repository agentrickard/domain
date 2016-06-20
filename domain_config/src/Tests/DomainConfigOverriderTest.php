<?php

namespace Drupal\domain_config\Tests;

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
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(5);
    // Get the domain list.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    // Except for the default domain, the page title element should match what
    // is in the override files.
    // With a language context, based on how we have our files setup, we
    // expect the following outcomes:
    // - example.com name = 'Drupal' for English, 'Drupal' for Spanish.
    // - one.example.com name = 'One' for English, 'Drupal' for Spanish.
    // - two.example.com name = 'Two' for English, 'Dos' for Spanish.
    // - three.example.com name = 'Three' for English, 'Drupal' for Spanish.
    // - four.example.com name = 'Four' for English, 'Four' for Spanish.
    foreach ($domains as $domain) {
      // Test the login page, because our default homepages do not exist.
      $path = $domain->getPath() . 'user/login';
      $this->drupalGet($path);
      if ($domain->isDefault()) {
        $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
      }
      else {
        $this->assertRaw('<title>Log in | ' . $domain->label() . '</title>', 'Loaded the proper site name.');
      }
      foreach ($this->langcodes as $langcode => $language) {
        $path = $domain->getPath() . $langcode . '/user/login';
        $this->drupalGet($path);
        if ($domain->isDefault()) {
          $this->assertRaw('<title>Log in | Drupal</title>', 'Loaded the proper site name.');
        }
        else {
          $this->assertRaw('<title>Log in | ' . $this->expectedName($domain) . '</title>', 'Loaded the proper site name.');
        }
      }
    }
  }

  /**
   * Returns the expected site name value from our test configuration.
   *
   * @param DomainInterface $domain
   *   The Domain object.
   *
   * @return string
   *   The expected name.
   */
  private function expectedName(DomainInterface $domain) {
    $name = '';

    switch ($domain->id()) {
      case 'one_example_com':
      case 'three_example_com':
        $name = 'Drupal';
        break;

      case 'two_example_com':
        $name = 'Dos';
        break;

      case 'four_example_com':
        $name = 'Four';
        break;
    }

    return $name;
  }

}
