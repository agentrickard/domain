<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests behavior for the domain list builder.
 *
 * @group domain_alias
 */
class DomainAliasListBuilderTest extends DomainAliasTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_alias', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 5 domains.
    $this->domainCreateTestDomains(5);
  }

  /**
   * Basic test setup.
   */
  public function testDomainListBuilder() {
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'administer domain aliases',
      'view domain aliases',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Check that links are printed.
    foreach ($this->getDomains() as $domain) {
      $href = 'admin/config/domain/alias/' . $domain->id();
      $this->assertSession()->linkByHrefExists($href, 0, 'Link found');
    }

    // Now login as a user with limited rights.
    $account = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'edit assigned domains',
      'view domain list',
      'view domain aliases',
      'edit domain aliases',
    ]);
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DOMAIN_ADMIN_FIELD);
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $user_storage->load($account->id());
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');

    $this->drupalLogin($account);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Check that links are printed.
    $path = 'admin/config/domain/';
    $this->drupalGet($path);
    foreach ($this->getDomains() as $domain) {
      $href = 'admin/config/domain/alias/' . $domain->id();
      if (in_array($domain->id(), $ids)) {
        $this->assertSession()->linkByHrefExists($href, 0, 'Link found');
      }
      else {
        $this->assertSession()->linkByHrefNotExists($href, 'Link not found');
      }
    }

    // Check access to the pages/routes.
    foreach ($this->getDomains() as $domain) {
      $path = 'admin/config/domain/alias/' . $domain->id();
      $this->drupalGet($path);
      if (in_array($domain->id(), $ids)) {
        $this->assertSession()->statusCodeEquals(200);
      }
      else {
        $this->assertSession()->statusCodeEquals(403);
      }
    }
  }

}
