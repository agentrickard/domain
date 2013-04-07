<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainValidate
 */

namespace Drupal\domain\Tests;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record creation API.
 */
class DomainValidate extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain record validation',
      'description' => 'Tests domain record validation.',
      'group' => 'Domain',
    );
  }

  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains();

    $domain = domain_load(1);

    // Our testing server should be able to acess the test PNG file.
    $domain->checkResponse();
    $this->assertTrue($domain->response == 200, format_string('Server test for @url passed.', array('@url' => $domain->path)));

    // Now create a bad domain.
    $values = array(
      'hostname' => 'foo.bar',
      'machine_name' => 'foo_bar',
      'name' => 'Foo',
    );
    $domain = domain_create(FALSE, $values);

    $domain->save();
    $domain = domain_load(2);
    $domain->checkResponse();
    $this->assertTrue($domain->response == 500, format_string('Server test for @url failed.', array('@url' => $domain->path)));
  }
}
