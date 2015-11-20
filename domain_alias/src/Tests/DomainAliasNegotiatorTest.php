<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Tests\DomainAliasNegotiatorTest
 */

namespace Drupal\domain_alias\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain_alias\Tests\DomainAliasTestBase;

/**
 * Tests domain alias request negotiation.
 *
 * @group domain_alias
 */
class DomainAliasNegotiatorTest extends DomainAliasTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'block');

  /**
   * Tests the handling of aliased requests.
   */
  function testDomainAliasNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create two new domains programmatically.
    $this->domainCreateTestDomains(2);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

    // Test the response of the default home page.
    foreach (\Drupal::service('domain.loader')->loadMultiple() as $domain) {
      if (!isset($alias_domain)) {
        $alias_domain = $domain;
      }
      $this->drupalGet($domain->getPath());
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
      $this->assertRaw('Exact match', 'Direct domain match.');
    }

    // Now, test an alias.
    $this->domainAliasCreateTestAlias($alias_domain);
    $pattern = '*.' . $alias_domain->getHostname();
    $alias = \Drupal::service('domain_alias.loader')->loadByPattern($pattern);
    $alias_domain->set('hostname', 'two.' . $alias_domain->getHostname());
    $alias_domain->setPath();
    $url = $alias_domain->getPath();
    $this->drupalGet($url);
    $this->assertRaw($alias_domain->label(), 'Loaded the proper domain.');
    $this->assertRaw('ALIAS:', 'No direct domain match.');
    $this->assertRaw($alias->getPattern(), 'Alias match.');

    // Test redirections.
    // @TODO: This could be much more elegant: the redirects break assertRaw()
    $alias->set('redirect', 301);
    $alias->save();
    $this->drupalGet($url);
    $alias->set('redirect', 302);
    $alias->save();
    $this->drupalGet($url);
    // Revoke the permission change
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('administer domains'));

  }

}
