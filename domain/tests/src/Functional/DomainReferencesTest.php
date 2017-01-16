<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for hook_domain_references_alter().
 *
 * The module suite ships with two field types -- admin and editor. We want to ensure
 * that these are filtered properly by hook_domain_references_alter().
 *
 * @group domain
 */
class DomainReferencesTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui', 'user');

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
    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer users',
      'administer domains',
    ));
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);

    // Create a user through the form.
    $this->fillField('name', 'testuser');
    $this->fillField('mail', 'test@example.com');
    $this->fillField('pass[pass1]', 'test');
    $this->fillField('pass[pass2]', 'test');

    // We expect to find 5 domain options. We set two as selected.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    $count = 0;
    $ids = ['example_com', 'one_example_com', 'two_example_com'];
    foreach ($domains as $domain) {
      $locator = DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if (in_array($domain->id(), $ids)) {
        $this->checkField($locator);
      }
      $locator = DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
    }

    // Find the all affiliates field.
    $locator = DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->findField($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $storage->load(3);
    // Check that two values are set.
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 3, 'User saved with three domain records.');

    // Now login as a user with limited rights.
    $account = $this->drupalCreateUser(array(
      'administer users',
      'assign domain administrators',
    ));
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DOMAIN_ADMIN_FIELD);
    $tester = $storage->load($account->id());
    $values = $manager->getFieldValues($tester, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');
    $storage->resetCache(array($account->id()));
    $this->drupalLogin($account);

    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domains as $domain) {
      $locator = DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
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
      $locator = DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->assertSession()->fieldNotExists($locator);
    }

    // The all affiliates field should not be present..
    $locator = DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->assertSession()->fieldNotExists($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Now, check the user.
    $storage->resetCache(array($user->id()));
    $user = $storage->load($user->id());
    // Check that two values are set.
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');

    // Now login as a user with different limited rights.
    $account = $this->drupalCreateUser(array(
      'administer users',
      'assign domain administrators',
      'assign domain editors',
    ));
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DOMAIN_ADMIN_FIELD);
    $new_ids = ['one_example_com', 'four_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $new_ids, DOMAIN_ACCESS_FIELD);

    $tester = $storage->load($account->id());
    $values = $manager->getFieldValues($tester, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');
    $values = $manager->getFieldValues($tester, DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');
    $storage->resetCache(array($account->id()));
    $this->drupalLogin($account);

    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    foreach ($domains as $domain) {
      $locator = DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
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
      // Some Domain Access field rights exist for this user. This adds one to the count.
      $locator = DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      if (in_array($domain->id(), $new_ids)) {
        $this->findField($locator);
        $this->checkField($locator);
      }
      else {
        $this->assertSession()->fieldNotExists($locator);
      }
    }

    // The all affiliates field should not be present..
    $locator = DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->assertSession()->fieldNotExists($locator);

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Now, check the user.
    $storage->resetCache(array($user->id()));
    $user = $storage->load($user->id());
    // Check that two values are set.
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');
    $values = $manager->getFieldValues($user, DOMAIN_ACCESS_FIELD);
    $this->assert(count($values) == 3, 'User saved with three domain records.');

  }

}
