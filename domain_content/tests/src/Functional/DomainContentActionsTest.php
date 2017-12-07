<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Tests the assign / unassign actions on a Domain Content view.
 *
 * @group domain_content
 */
class DomainContentActionsTest extends DomainContentTestBase {

  public function testDomainContentActions() {
    $this->assertTrue(count($this->domains) == 5);
  }

}

