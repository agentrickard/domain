<?php

namespace Drupal\domain\Tests;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests domain record HTTP response.
 *
 * Note: Class is called DomainIResponseTest on purpose as for some
 * unexplained reason when it is DomainResponseTest and runs after
 * DomainNegotiatorTest, it plain fails due to timeout (no matter how high it
 * gets set in DomainValidator::checkResponse().
 *
 * @group domain
 */
class DomainIResponseTest extends DomainTestBase {

  /**
   * Tests that a domain response is proper.
   */
  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains();

    // Check the created domain based on it's known id value.
    $key = 'example_com';
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = \Drupal::service('domain.loader')->load($key);

    // Our testing server should be able to access the test PNG file.
    $this->assertTrue($domain->getResponse() == 200, new FormattableMarkup('Server test for @url passed.', array('@url' => $domain->getPath())));

    // Now create a bad domain.
    $values = array(
      'hostname' => 'foo.bar',
      'id' => 'foo_bar',
      'name' => 'Foo',
    );
    $domain = \Drupal::service('domain.creator')->createDomain($values);

    $domain->save();
    $this->assertTrue($domain->getResponse() == 500, new FormattableMarkup('Server test for @url failed.', array('@url' => $domain->getPath())));
  }

}
