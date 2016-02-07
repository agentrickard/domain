<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainValidatorTest.
 */

namespace Drupal\domain\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain\Tests\DomainTestBase;

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
  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains();

    // Check the created domain based on it's known id value.
    $key = 'example_com';
    $domain = \Drupal::service('domain.loader')->load($key);

    // Our testing server should be able to acess the test PNG file.
    $this->assertTrue($domain->getResponse() == 200, format_string('Server test for @url passed.', array('@url' => $domain->getPath())));

    // Now create a bad domain.
    $values = array(
      'hostname' => 'foo.bar',
      'id' => 'foo_bar',
      'name' => 'Foo',
    );
    $domain = \Drupal::service('domain.creator')->createDomain($values);

    $domain->save();
    $this->assertTrue($domain->getResponse() == 500, format_string('Server test for @url failed.', array('@url' => $domain->getPath())));
  }
}
