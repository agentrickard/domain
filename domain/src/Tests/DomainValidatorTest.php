<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainValidatorTest.
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests domain record validation.
 *
 * @group domain
 */
class DomainValidatorTest extends DomainTestBase {

  /**
   * Tests that a domain response is proper.
   * @TODO: This class needs a rewrite.
   */
  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains();

    // @TODO: We need a new loader?
    $key = domain_machine_name(domain_hostname());
    $domain = domain_load($key);

    // Our testing server should be able to acess the test PNG file.
    $this->assertTrue($domain->getResponse() == 200, format_string('Server test for @url passed.', array('@url' => $domain->getPath())));

    // Now create a bad domain.
    $values = array(
      'hostname' => 'foo.bar',
      'id' => 'foo_bar',
      'name' => 'Foo',
    );
    $domain = domain_create(FALSE, $values);

    $domain->save();
    $this->assertTrue($domain->getResponse() == 500, format_string('Server test for @url failed.', array('@url' => $domain->getPath())));
  }
}
