<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests behavior for environment loading on the overview page.
 *
 * @group domain_alias
 */
class DomainAliasListHostnameTest extends DomainAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 3 domains. These will be example.com, one.example.com,
    // two.example.com.
    $this->domainCreateTestDomains(3);
  }

  /**
   * Test for environment matching.
   */
  public function testDomainAliasEnvironments() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $alias_loader = \Drupal::entityTypeManager()->getStorage('domain_alias');
    $domains = $domain_storage->loadMultiple();

    $base = $this->baseHostname;
    $hostnames = [$base, 'one.' . $base, 'two.' . $base];

    // Our patterns should map to example.com, one.example.com, two.example.com.
    $patterns = ['*.' . $base, 'four.' . $base, 'five.' . $base];
    $i = 0;
    foreach ($domains as $domain) {
      $this->assertEquals($hostnames[$i], $domain->getHostname(), 'Hostnames set correctly');
      $this->assertEquals($hostnames[$i], $domain->getCanonical(), 'Canonical domains set correctly');
      $values = [
        'domain_id' => $domain->id(),
        'pattern' => array_shift($patterns),
        'redirect' => 0,
        'environment' => 'local',
      ];
      $this->createDomainAlias($values);
      $i++;
    }
    // Test the environment loader.
    $local = $alias_loader->loadByEnvironment('local');
    $this->assertCount(3, $local, 'Three aliases set to local');
    // Test the environment matcher. $domain here is two.example.com.
    $match = $alias_loader->loadByEnvironmentMatch($domain, 'local');
    $this->assertCount(1, $match, 'One environment match loaded');
    $alias = current($match);
    $this->assertEquals('five.' . $base, $alias->getPattern(), 'Proper pattern match loaded.');

    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($admin);

    // Load an aliased domain.
    $this->drupalGet($domain->getScheme() . 'five.' . $base . '/admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Save the form.
    $this->pressButton('edit-submit');
    // Ensure the values haven't changed.
    $i = 0;
    $domains = $domain_storage->loadMultiple();
    foreach ($domains as $domain) {
      $this->assertEquals($hostnames[$i], $domain->getHostname(), 'Hostnames set correctly');
      $this->assertEquals($hostnames[$i], $domain->getCanonical(), 'Canonical domains set correctly');
      $i++;
    }
  }

}
