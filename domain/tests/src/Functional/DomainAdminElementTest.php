<?php

namespace Drupal\Tests\domain\Functional;

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
  public static $modules = ['domain', 'field', 'field_ui', 'user'];

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
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer users',
      'administer domains',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);

    // Create a user through the form.
    $this->fillField('name', 'testuser');
    $this->fillField('mail', 'test@example.com');
    $this->fillField('pass[pass1]', 'test');
    $this->fillField('pass[pass2]', 'test');

    // We expect to find 5 domain options. We set two as selected.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
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
    $user = $storage->load(3);
    // Check that two values are set.
    $manager = \Drupal::service('domain.element_manager');
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 3, 'User saved with three domain records.');

    // Now login as a user with limited rights.
    $account = $this->drupalCreateUser([
      'administer users',
      'assign domain administrators',
    ]);
    $ids = ['example_com', 'one_example_com'];
    $this->addDomainsToEntity('user', $account->id(), $ids, DOMAIN_ADMIN_FIELD);
    $tester = $storage->load($account->id());
    $values = $manager->getFieldValues($tester, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');
    $storage->resetCache([$account->id()]);
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

    // Now, check the user.
    $storage->resetCache([$user->id()]);
    $user = $storage->load($user->id());
    // Check that two values are set.
    $values = $manager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    $this->assert(count($values) == 2, 'User saved with two domain records.');

    // Test the case presented in https://www.drupal.org/node/2841962.
    $config = \Drupal::configFactory()->getEditable('user.settings');
    $config->set('verify_mail', 0);
    $config->set('register', USER_REGISTER_VISITORS);
    $config->save();
    $this->drupalLogout();
    $this->drupalGet('user/register');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains('Domain administrator');
    foreach ($domains as $domain) {
      $locator = DOMAIN_ADMIN_FIELD . '[' . $domain->id() . ']';
      $this->assertSession()->fieldNotExists($locator);
    }
    // Create a user through the form.
    $this->fillField('name', 'testuser2');
    $this->fillField('mail', 'test2@example.com');
    // In 8.3, this field is not present?
    if (!empty($this->findField('pass[pass1]'))) {
      $this->fillField('pass[pass1]', 'test');
      $this->fillField('pass[pass2]', 'test');
    }
    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

}
