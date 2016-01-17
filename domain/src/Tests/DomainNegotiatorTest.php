<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainNegotiatorTest.
 */

namespace Drupal\domain\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain\Tests\DomainTestBase;

/**
 * Tests the domain negotation manager.
 *
 * @group domain
 */
class DomainNegotiatorTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test', 'block');

  /**
   * Tests the handling of an inbound request.
   */
  function testDomainNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('view domain information'));

    // Test the response of the default home page.
    foreach (\Drupal::service('domain.loader')->loadMultiple() as $domain) {
      $this->drupalGet($domain->getPath());
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
    }

    // Revoke the permission change
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('view domain information'));

    // @TODO: Any other testing needed here?

  }

}
