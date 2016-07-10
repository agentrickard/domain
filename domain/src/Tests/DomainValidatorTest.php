<?php

namespace Drupal\domain\Tests;

/**
 * Tests domain record validation.
 *
 * @group domain
 */
class DomainValidatorTest extends DomainTestBase {

  /**
   * Tests that a domain response is proper.
   *
   * @TODO: This class checks for proper responses, and should be moved to a
   * new class. What we want to test here are the validation rules for creating
   * a domain.
   */
  public function testDomainValidator() {
    // No domains should exist.
    $this->domainTableIsEmpty();
  }

}
