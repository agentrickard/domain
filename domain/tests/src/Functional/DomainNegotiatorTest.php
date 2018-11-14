<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests the domain negotiation manager.
 *
 * @group domain
 */
class DomainNegotiatorTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_test', 'block'];

  /**
   * Tests the handling of an inbound request.
   */
  public function testDomainNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['view domain information']);

    // Test the response of the default home page.
    foreach (\Drupal::entityTypeManager()->getStorage('domain')->loadMultiple() as $domain) {
      $this->drupalGet($domain->getPath());
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
    }
  }

}
