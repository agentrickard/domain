<?php

namespace Drupal\Tests\domain_alias\Functional;

use Drupal\Tests\domain_alias\Functional\DomainAliasTestBase;

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
    $domain = \Drupal::service('entity_type.manager')->getStorage('domain')->loadByHostname($key);
    $this->assertTrue(!empty($domain), 'Test domain created.');

    // Valid patterns to test. Valid is the boolean value.
    $patterns = [
      'localhost' => 1,
      'example.com' => 1,
      'www.example.com' => 1, // see www-prefix test, below.
      '*.example.com' => 1,
      'one.example.com' => 1,
      'example.com:8080' => 1,
      'foobar' => 0, // must have a dot or be localhost
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
    $config->set('allow_non_ascii', true)->save();
    // Valid hostnames to test. Valid is the boolean value.
    $patterns = [
      'éxample.com' => 1, // ascii-only allowed.
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
