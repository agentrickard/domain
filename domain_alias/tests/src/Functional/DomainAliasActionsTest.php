<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests the domain record actions on environments.
 *
 * @group domain_alias
 */
class DomainAliasActionsTest extends DomainAliasTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['domain', 'domain_alias', 'user'];

  /**
   * Tests bulk actions through the domain overview page.
   */
  public function testDomainActions() {
    $this->admin_user = $this->drupalCreateUser(['administer domains', 'access administration pages']);
    $this->drupalLogin($this->admin_user);

    // Create test domains.
    $this->domainCreateTestDomains(3);

    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $alias_loader = \Drupal::entityTypeManager()->getStorage('domain_alias');
    $domains = $domain_storage->loadMultiple();

    // Save these for later testing.
    $original_domains = $domains;

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

    $path = $domain->getScheme() . 'five.' . $base . '/admin/config/domain';

    // Visit the domain overview administration page.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    // Test the domains.
    $domains = $domain_storage->loadMultiple();
    $this->assertCount(3, $domains, 'Three domain records found.');

    // Check the default domain.
    $default = $domain_storage->loadDefaultId();
    $key = 'example_com';
    $this->assertEquals($default, $key, 'Default domain set correctly.');

    // Test some text on the page.
    foreach ($domains as $domain) {
      $name = $domain->label();
      $this->assertSession()->pageTextContains($name);
    }
    // Test the list of actions.
    $actions = ['delete', 'disable', 'default'];
    foreach ($actions as $action) {
      $this->assertSession()->responseContains("/domain/{$action}/");
    }
    // Check that all domains are active.
    $this->assertSession()->responseNotContains('Inactive');

    // Disable a domain and test the enable link.
    $this->clickLink('Disable', 0);
    $this->assertSession()->responseContains('Inactive');

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domain_storage->loadMultiple() as $domain) {
      if ($domain->id() == 'one_example_com') {
        $this->assertEmpty($domain->status(), 'One domain inactive.');
      }
      else {
        $this->assertNotEmpty($domain->status(), 'Other domains active.');
      }
    }

    // Test the list of actions.
    $actions = ['enable', 'delete', 'disable', 'default'];
    foreach ($actions as $action) {
      $this->assertSession()->responseContains("/domain/{$action}/");
    }
    // Re-enable the domain.
    $this->clickLink('Enable', 0);
    $this->assertSession()->responseNotContains('Inactive');

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domain_storage->loadMultiple() as $domain) {
      $this->assertNotEmpty($domain->status(), 'All domains active.');
    }

    // Set a new default domain.
    $this->clickLink('Make default', 0);

    // Visit the domain overview administration page to clear cache.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    // Check the default domain.
    $domain_storage->resetCache();
    $default = $domain_storage->loadDefaultId();
    $key = 'one_example_com';
    $this->assertEquals($default, $key, 'Default domain set correctly.');

    // Did the hostnames change accidentally?
    foreach ($domain_storage->loadMultiple() as $id => $domain) {
      $this->assertEquals($original_domains[$id]->getHostname(), $domain->getHostname(), 'Hostnames match.');
    }

  }

}
