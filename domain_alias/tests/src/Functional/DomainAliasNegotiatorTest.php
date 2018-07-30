<?php

namespace Drupal\Tests\domain_alias\Functional;

use Drupal\user\RoleInterface;

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
  public static $modules = ['domain', 'domain_alias', 'user', 'block'];

  /**
   * Tests the handling of aliased requests.
   */
  public function testDomainAliasNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create two new domains programmatically.
    $this->domainCreateTestDomains(2);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['administer domains']);

    // Set the storage handles.
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $alias_storage = \Drupal::entityTypeManager()->getStorage('domain_alias');

    // Set known prefixes that work with our tests. This will give us domains
    // 'example.com' and 'one.example.com' aliased to 'two.example.com' and
    // 'three.example.com'.
    $prefixes = ['two', 'three'];
    // Test the response of each home page.
    /** @var \Drupal\domain\Entity\Domain $domain */
    foreach ($domain_storage->loadMultiple() as $domain) {
      $alias_domains[] = $domain;
      $this->drupalGet($domain->getPath());
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
      $this->assertRaw('Exact match', 'Direct domain match.');
    }

    // Now, test an alias for each domain.
    foreach ($alias_domains as $index => $alias_domain) {
      $prefix = $prefixes[$index];
      // Set a known pattern.
      $pattern = $prefix . '.' . $this->baseHostname;
      $this->domainAliasCreateTestAlias($alias_domain, $pattern);
      $alias = $alias_storage->loadByPattern($pattern);
      // Set the URL for the request. Note that this is not saved, it is just
      // URL generation.
      $alias_domain->set('hostname', $pattern);
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
    }
    // Test a wildcard alias.
    // @TODO: Refactor this test to merge with the above.
    $alias_domain = $domain_storage->loadDefaultDomain();
    $pattern = '*.' . $this->baseHostname;
    $this->domainAliasCreateTestAlias($alias_domain, $pattern);
    $alias = $alias_storage->loadByPattern($pattern);
    // Set the URL for the request. Note that this is not saved, it is just
    // URL generation.
    $alias_domain->set('hostname', 'four.' . $this->baseHostname);
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

    // Revoke the permission change.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['administer domains']);
  }

}
