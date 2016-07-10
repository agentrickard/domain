<?php

namespace Drupal\domain\Tests;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests domain record validation.
 *
 * @group domain
 */
class DomainValidatorTest extends DomainTestBase {

  /**
   * Tests that a domain hostname validates.
   */
  public function testDomainValidator() {
    // No domains should exist.
    $this->domainTableIsEmpty();
    $creator = \Drupal::service('domain.creator');
    $validator = \Drupal::service('domain.validator');

    // Create a domain.
    $this->domainCreateTestDomains(1, 'foo.com');
    // Check the created domain based on it's known id value.
    $key = 'foo.com';
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = \Drupal::service('domain.loader')->loadByHostname($key);
    $this->assertTrue(!empty($domain), 'Test domain created.');

    // Valid hostnames to test. Valid is the boolean value.
    $hostnames = [
      'localhost' => 1,
      'example.com' => 1,
      'www.example.com' => 1, // see www-prefix test, below.
      'one.example.com' => 1,
      'example.com:8080' => 1,
      'example.com::8080' => 0, // only one colon.
      'example.com:abc' => 0, // no letters after a colon.
      '.example.com' => 0, // cannot beging with a dot.
      'example.com.' => 0, // cannot end with a dot.
      'EXAMPLE.com' => 0, // lowercase only.
      'éxample.com' => 0, // ascii-only.
      'foo.com' => 0, // duplicate.
    ];
    foreach ($hostnames as $hostname => $valid) {
      $domain = $creator->createDomain(['hostname' => $hostname]);
      $errors = $validator->validate($domain);
      if ($valid) {
        $this->assertTrue(empty($errors), new FormattableMarkup('Validation test for @hostname passed.', array('@hostname' => $hostname)));
      }
      else {
        $this->assertTrue(!empty($errors), new FormattableMarkup('Validation test for @hostname failed.', array('@hostname' => $hostname)));
      }
    }

  }

}
