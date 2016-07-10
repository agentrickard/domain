<?php

namespace Drupal\domain_alias\Tests;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests domain alias record validation.
 *
 * @group domain_alias
 */
class DomainAliasValidatorTest extends DomainAliasTestBase {

  /**
   * Tests that a domain hostname validates.
   */
  public function testDomainAliasValidator() {
    // No domains should exist.
    $this->domainTableIsEmpty();
    $validator = \Drupal::service('domain_alias.validator');

    // Create a domain.
    $this->domainCreateTestDomains(1, 'foo.com');
    // Check the created domain based on it's known id value.
    $key = 'foo.com';
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = \Drupal::service('domain.loader')->loadByHostname($key);
    $this->assertTrue(!empty($domain), 'Test domain created.');
//dt Drupal\\domain_alias\\Tests\\DomainAliasValidatorTest c
    // Valid patterns to test. Valid is the boolean value.
    $patterns = [
      'localhost' => 1,
      'example.com' => 1,
      'www.example.com' => 1, // see www-prefix test, below.
      '*.example.com' => 1,
      'one.example.com' => 1,
      'example.com:8080' => 1,
      '*.*.example.com' => 0, // only one wildcard.
      'example.com::8080' => 0, // only one colon.
      'example.com:abc' => 0, // no letters after a colon.
      '.example.com' => 0, // cannot begin with a dot.
      'example.com.' => 0, // cannot end with a dot.
      'EXAMPLE.com' => 0, // lowercase only.
      'éxample.com' => 0, // ascii-only.
      'foo.com' => 0, // duplicate.
    ];
    foreach ($patterns as $pattern => $valid) {
      $alias = $this->domainAliasCreateTestAlias($domain, $pattern, 0, FALSE);
      $errors = $validator->validate($alias);
      if ($valid) {
        $this->assertTrue(empty($errors), new FormattableMarkup('Validation test for @pattern passed.', array('@pattern' => $pattern)));
      }
      else {
        $this->assertTrue(!empty($errors), new FormattableMarkup('Validation test for @pattern failed.', array('@pattern' => $pattern)));
      }
    }
    // Test the configurable option.
    $config = $this->config('domain.settings');
    $config->set('allow_non_ascii', true)->save();
    // Valid hostnames to test. Valid is the boolean value.
    $patterns = [
      'éxample.com' => 1, // ascii-only allowed.
    ];
    foreach ($patterns as $pattern => $valid) {
      $alias = $this->domainAliasCreateTestAlias($domain, $pattern, 0, FALSE);
      $errors = $validator->validate($alias);
      if ($valid) {
        $this->assertTrue(empty($errors), new FormattableMarkup('Validation test for @pattern passed.', array('@pattern' => $pattern)));
      }
      else {
        $this->assertTrue(!empty($errors), new FormattableMarkup('Validation test for @pattern failed.', array('@pattern' => $pattern)));
      }
    }

  }

}
