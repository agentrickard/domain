<?php

namespace Drupal\Tests\domain_alias\Functional;

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
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->loadByHostname($key);
    $this->assertTrue(!empty($domain), 'Test domain created.');

    // Valid patterns to test. Valid is the boolean value.
    $patterns = [
      'localhost' => 1,
      'example.com' => 1,
       // See www-prefix test, below.
      'www.example.com' => 1,
      '*.example.com' => 1,
      'one.example.com' => 1,
      'example.com:8080' => 1,
       // Must have a dot or be localhost.
      'foobar' => 0,
       // Only one wildcard.
      '*.*.example.com' => 0,
       // Only one colon.
      'example.com::8080' => 0,
       // No letters after a colon.
      'example.com:abc' => 0,
       // Cannot begin with a dot.
      '.example.com' => 0,
       // Cannot end with a dot.
      'example.com.' => 0,
       // Lowercase only.
      'EXAMPLE.com' => 0,
       // ascii-only.
      'éxample.com' => 0,
       // duplicate.
      'foo.com' => 0,
    ];
    foreach ($patterns as $pattern => $valid) {
      $alias = $this->domainAliasCreateTestAlias($domain, $pattern, 0, 'default', FALSE);
      $errors = $validator->validate($alias);
      if ($valid) {
        $this->assertTrue(empty($errors), 'Validation test success.');
      }
      else {
        $this->assertTrue(!empty($errors), 'Validation test success.');
      }
    }
    // Test the configurable option.
    $config = $this->config('domain.settings');
    $config->set('allow_non_ascii', TRUE)->save();
    // Valid hostnames to test. Valid is the boolean value.
    $patterns = [
      // ascii-only allowed.
      'éxample.com' => 1,
    ];
    foreach ($patterns as $pattern => $valid) {
      $alias = $this->domainAliasCreateTestAlias($domain, $pattern, 0, 'default', FALSE);
      $errors = $validator->validate($alias);
      if ($valid) {
        $this->assertTrue(empty($errors), 'Validation test success.');
      }
      else {
        $this->assertTrue(!empty($errors), 'Validation test success.');
      }
    }
  }

}
