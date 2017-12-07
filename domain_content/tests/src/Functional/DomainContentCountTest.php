<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Creates editors and users and count them on the overview page
 *
 * @group domain_content
 */
class DomainContentCountTest extends DomainContentTestBase {

  public function testDomainContentCount() {
    $this->assertTrue(count($this->domains) == 5);
  }

}
