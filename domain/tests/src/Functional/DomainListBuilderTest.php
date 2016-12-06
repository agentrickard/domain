<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the domain list builder.
 *
 * @group domain
 */
class DomainListBuilderTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'user');

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
    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ));
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Check that links are printed.
    foreach ($this->getDomains() as $domain) {
      $href = 'admin/config/domain/edit/' . $domain->id();
      $this->assertSession()->linkByHrefExists($href, 0, 'Link found');
      $this->assertSession()->assertEscaped($domain->label());
    }

    // Now login as a user with limited rights.
    $account = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'edit assigned domains',
      'view domain list',
    ));
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
      $href = 'admin/config/domain/edit/' . $domain->id();
      if (in_array($domain->id(), $ids)) {
        $this->assertSession()->linkByHrefExists($href, 0, 'Link found');
        $this->assertSession()->assertEscaped($domain->label());
      }
      else {
        $this->assertSession()->linkByHrefNotExists($href, 'Link not found');
        $this->assertSession()->assertEscaped($domain->label());
      }
    }

    // Check access to the pages/routes.
    foreach ($this->getDomains() as $domain) {
      $path = 'admin/config/domain/edit/' . $domain->id();
      $this->drupalGet($path);
      if (in_array($domain->id(), $ids)) {
        $this->assertSession()->statusCodeEquals(200);
      }
      else {
        $this->assertSession()->statusCodeEquals(403);
      }
    }

    // Now login as a user with more limited rights.
    $account2 = $this->drupalCreateUser(array(
      'create article content',
      'edit any article content',
      'edit assigned domains',
      'view assigned domains',
    ));
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account2->id(), $ids, DOMAIN_ADMIN_FIELD);
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $user_storage->load($account2->id());
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');

    $this->drupalLogin($account2);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Check that domains are listed and links are printed.
    $path = 'admin/config/domain/';
    $this->drupalGet($path);
    foreach ($this->getDomains() as $domain) {
      $href = 'admin/config/domain/edit/' . $domain->id();
      if (in_array($domain->id(), $ids)) {
        $this->assertSession()->linkByHrefExists($href, 0, 'Link found');
        $this->assertSession()->assertEscaped($domain->label());
      }
      else {
        $this->assertSession()->linkByHrefNotExists($href, 'Link not found');
        $this->assertSession()->assertNoEscaped($domain->label());
      }
    }

    // Check access to the pages/routes.
    foreach ($this->getDomains() as $domain) {
      $path = 'admin/config/domain/edit/' . $domain->id();
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
