<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\domain\DomainInterface;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Tests behavior for hook_domain_references_alter().
 *
 * The module suite ships with two field types -- admin and editor. We want to
 * ensure that these are filtered properly by hook_domain_references_alter().
 *
 * @group domain
 */
class DomainReferencesTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'domain_access',
    'field',
    'field_ui',
    'user',
  ];

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
  public function testDomainReferences() {
    // Create an admin user. This will be user 2.
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer users',
      'administer domains',
      'assign domain editors',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);

    // Create a user through the form. This will be user 3.
    $this->fillField('name', 'testuser');
    $this->fillField('mail', 'test@example.com');
    $this->fillField('pass[pass1]', 'test');
    $this->fillField('pass[pass2]', 'test');

    // We expect to find 5 domain options. We set three as selected.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    $ids = ['example_com', 'one_example_com', 'two_example_com'];
    $edit_ids = ['example_com', 'one_example_com'];
    foreach ($domains as $domain) {
      $locator = DomainInterface::DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if (in_array($domain->id(), $ids)) {
        $this->checkField($locator);
      }
      $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if (in_array($domain->id(), $edit_ids)) {
        $this->checkField($locator);
      }
    }

    // Find the all affiliates field.
    $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->findField($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Load our test user.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $testuser = $storage->load(3);
    // Check that three values are set.
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($testuser, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 3, 'User saved with three domain admin records.');
    // Check that no access fields are set.
    $values = $manager->getFieldValues($testuser, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain access records.');

    // Now login as a user with limited rights. This is user 4.
    $account = $this->drupalCreateUser([
      'administer users',
      'assign domain administrators',
    ]);
    // Set some domain assignments for this user.
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DomainInterface::DOMAIN_ADMIN_FIELD);
    $limited_admin = $storage->load($account->id());
    $values = $manager->getFieldValues($limited_admin, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain admin records.');
    // Check that no access fields are set.
    $values = $manager->getFieldValues($limited_admin, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 0, 'User saved with no domain access records.');

    // Now edit user 3 as user 4 with limited rights.
    $this->drupalLogin($account);
    $this->drupalGet('user/' . $testuser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domains as $domain) {
      $locator = DomainInterface::DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if ($domain->id() == 'example_com') {
        $this->checkField($locator);
      }
      elseif ($domain->id() == 'one_example_com') {
        $this->uncheckField($locator);
      }
      else {
        $this->assertSession()->fieldNotExists($locator);
      }
      // No Domain Access field rights exist for this user.
      $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->assertSession()->fieldNotExists($locator);
    }

    // The all affiliates field should not be present..
    $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->assertSession()->fieldNotExists($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Now, check the user.
    $storage->resetCache([$testuser->id()]);
    $testuser = $storage->load($testuser->id());
    // Check that two values are set.
    $values = $manager->getFieldValues($testuser, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain admin records.');
    // Check that no access fields are set.
    $values = $manager->getFieldValues($testuser, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain access records.');

    // Now login as a user with different limited rights. This is user 5.
    $new_account = $this->drupalCreateUser([
      'administer users',
      'assign domain administrators',
      'assign domain editors',
    ]);
    $ids = ['example_com', 'one_example_com'];
    $new_ids = ['one_example_com', 'four_example_com'];
    $this->addDomainsToEntity('user', $new_account->id(), $ids, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->addDomainsToEntity('user', $new_account->id(), $new_ids, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);

    $new_admin = $storage->load($new_account->id());
    $values = $manager->getFieldValues($new_admin, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain admin records.');
    $values = $manager->getFieldValues($new_admin, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain access records.');

    // Now edit the user as someone with limited rights.
    $storage->resetCache([$new_admin->id()]);
    $this->drupalLogin($new_account);

    $this->drupalGet('user/' . $testuser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domains as $domain) {
      $locator = DomainInterface::DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if ($domain->id() == 'example_com') {
        $this->checkField($locator);
      }
      elseif ($domain->id() == 'one_example_com') {
        $this->uncheckField($locator);
      }
      else {
        $this->assertSession()->fieldNotExists($locator);
      }
      // Some Domain Access field rights exist for this user. This adds
      // one to the count.
      $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      if (in_array($domain->id(), $new_ids)) {
        $this->findField($locator);
        $this->checkField($locator);
      }
      else {
        $this->assertSession()->fieldNotExists($locator);
      }
    }

    // The all affiliates field should not be present..
    $locator = DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->assertSession()->fieldNotExists($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Now, check the user.
    $storage->resetCache([$testuser->id()]);
    $testuser = $storage->load($testuser->id());
    // Check that two values are set.
    $values = $manager->getFieldValues($testuser, DomainInterface::DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain admin records.');
    $values = $manager->getFieldValues($testuser, DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 3, 'User saved with three domain access records.');

  }

}
