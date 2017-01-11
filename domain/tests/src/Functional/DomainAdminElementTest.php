<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the domain admin field element.
 *
 * @group domain
 */
class DomainAdminElementTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'field', 'field_ui', 'user');

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
  public function testDomainAccessElement() {
    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer users',
      'administer domains',
    ));
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);

    // Set the title, so the node can be saved.
    $this->fillField('mail', 'test@example.com');

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
    }

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $storage->load(2);
    // Check that two values are set.
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
  #  $this->assert(count($values) == 3, 'User saved with three domain records.');

    // Now login as a user with limited rights.
    $account = $this->drupalCreateUser(array('administer users', 'assign domain administrators'));
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DOMAIN_ADMIN_FIELD);
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $user_storage->load($account->id());
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
  #  $this->assert(count($values) == 2, 'User saved with two domain records.');

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
    }

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Now, check the node.
    $storage->resetCache(array($user->id()));
    $user = $storage->load(2);
    // Check that two values are set.
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
#    $this->assert(count($values) == 2, 'User saved with two domain records.');
  }

}
