<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainManager
 */

namespace Drupal\domain\Tests;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record creation API.
 */
class DomainManager extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain manager tests',
      'description' => 'Tests domain response management.',
      'group' => 'Domain',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test', 'block');

  function testDomainManager() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

    $account = user_load(0, TRUE);
    $this->assertTrue(user_access('administer domains', $account), 'Anonymous user can view Domain Server block.');

    // Test the response of the default home page.
    foreach (domain_load_multiple() as $domain) {
      $this->drupalGet($domain->path);
      $this->assertRaw($domain->name, 'Loaded the proper domain.');
    }

    // Revoke the permission change
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

  }

}
