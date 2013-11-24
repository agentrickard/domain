<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasManagerTest.
 */

namespace Drupal\domain_alias\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain_alias\Tests\DomainAliasTestBase;

/**
 * Tests the domain record creation API.
 */
class DomainAliasManagerTest extends DomainAliasTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain alias manager tests',
      'description' => 'Tests domain alias response management.',
      'group' => 'Domain alias',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'block');

  function testDomainAliasManager() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create two new domains programmatically.
    $this->domainCreateTestDomains(2);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

    $account = user_load(0, TRUE);
    $this->assertTrue(user_access('administer domains', $account), 'Anonymous user can view Domain Server block.');

    // Test the response of the default home page.
    foreach (domain_load_multiple() as $domain) {
      if (!isset($alias_domain)) {
        $alias_domain = $domain;
      }
      $this->drupalGet($domain->path);
      $this->assertRaw($domain->name, 'Loaded the proper domain.');
      $this->assertRaw('<td>Domain match</td><td>TRUE</td>', 'Direct domain match.');
    }

    // Now, test an alias.
    $this->domainAliasCreateTestAlias($alias_domain);
    $pattern = '*.' . $alias_domain->hostname;
    $alias = domain_alias_pattern_load($pattern);
    $alias_domain->hostname = 'two.' . $alias_domain->hostname;
    $alias_domain->setPath();
    $url = $alias_domain->getPath();
    $this->drupalGet($url);
    $this->assertRaw($alias_domain->name, 'Loaded the proper domain.');
    $this->assertRaw('<td>Domain match</td><td>ALIAS:', 'No direct domain match.');
    $this->assertRaw($alias->pattern, 'Alias match.');

    // Test redirections.
    // @TODO: This could be much more elegant.
    $alias->redirect = 301;
    $alias->save();
    $this->drupalGet($url);
    $this->assertRaw($alias_domain->name, 'Loaded the proper domain.');
    $this->assertRaw('<td>Domain match</td><td>TRUE</td>', 'Direct domain match.');
    $alias->redirect = 302;
    $alias->save();
    $this->drupalGet($url);
    $this->assertRaw($alias_domain->name, 'Loaded the proper domain.');
    $this->assertRaw('<td>Domain match</td><td>TRUE</td>', 'Direct domain match.');
    // Revoke the permission change
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

  }

}
