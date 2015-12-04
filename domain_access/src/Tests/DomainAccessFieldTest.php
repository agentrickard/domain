<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessFieldTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;
use Drupal\node\Entity\NodeType;

/**
 * Tests the domain access entity reference field type.
 *
 * @group domain_access
 */
class DomainAccessFieldTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui', 'user');

  function setUp() {
    parent::setUp();

    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();
  }

  /**
   * Tests that the fields are accessed properly.
   */
  function testDomainAccessFields() {
    $label = 'Send to all affiliates';
    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Test a user who can access all domain settings.
    $user1 = $this->drupalCreateUser(array('create article content', 'publish to any domain'));
    $this->drupalLogin($user1);

    // Visit the article creation page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200, 'Article creation found.');

    // Check for the form options.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    foreach ($domains as $domain) {
      $this->assertText($domain->label(), 'Domain form item found.');
    }
    $this->assertText($label, 'All affiliates field found.');

    // Test a user who can access some domain settings.
    $user2 = $this->drupalCreateUser(array('create article content', 'publish to any assigned domain'));
    $active_domain = array_rand($domains, 1);
    $this->addDomainToEntity('user', $user2->id(), $active_domain);
    $this->drupalLogin($user2);

    // Visit the article creation page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200, 'Article creation found.');

    // Check for the form options.
    foreach ($domains as $domain) {
      if ($domain->id() == $active_domain) {
        $this->assertText($domain->label(), 'Domain form item found.');
      }
      else {
        $this->assertNoText($domain->label(), 'Domain form item not found.');
      }
    }
    $this->assertNoText($label, 'All affiliates field not found.');

    // Test a user who can access no domain settings.
    $user3 = $this->drupalCreateUser(array('create article content'));
    $this->drupalLogin($user3);

    // Visit the article creation page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200, 'Article creation found.');

    // Check for the form options.
    foreach ($domains as $domain) {
       $this->assertNoText($domain->label(), 'Domain form item not found.');
    }
    $this->assertNoText($label, 'All affiliates field not found.');

    // Test a user who can assign users to domains.
    $user4 = $this->drupalCreateUser(array('administer users', 'assign editors to any domain'));
    $this->drupalLogin($user4);

    // Visit the account creation page.
    $this->drupalGet('admin/people/create');
    $this->assertResponse(200, 'User creation found.');

    // Check for the form options.
    foreach ($domains as $domain) {
      $this->assertText($domain->label(), 'Domain form item found.');
    }

    // Test a user who can assign users to some domains.
    $user5 = $this->drupalCreateUser(array('administer users', 'assign domain editors'));
    $active_domain = array_rand($domains, 1);
    $this->addDomainToEntity('user', $user5->id(), $active_domain);
    $this->drupalLogin($user5);

    // Visit the account creation page.
    $this->drupalGet('admin/people/create');
    $this->assertResponse(200, 'User creation found.');

    // Check for the form options.
    foreach ($domains as $domain) {
      if ($domain->id() == $active_domain) {
        $this->assertText($domain->label(), 'Domain form item found.');
      }
      else {
        $this->assertNoText($domain->label(), 'Domain form item not found.');
      }
    }

    // Test a user who can access no domain settings.
    $user6 = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($user6);

    // Visit the account creation page.
    $this->drupalGet('admin/people/create');
    $this->assertResponse(200, 'User creation found.');

    // Check for the form options.
    foreach ($domains as $domain) {
       $this->assertNoText($domain->label(), 'Domain form item not found.');
    }

    // Test a user who can access all domain settings.
    $user7 = $this->drupalCreateUser(array('bypass node access', 'publish to any domain'));
    $this->drupalLogin($user7);

    // Create a new content type and test that the fields are created.
    // Create a content type programmatically.
    $type = $this->drupalCreateContentType();

    $type_exists = (bool) NodeType::load($type->id());
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');

    // Visit the article creation page.
    $this->drupalGet('node/add/' . $type->id());
    $this->assertResponse(200, $type->id() . ' creation found.');

    // Check for the form options.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    foreach ($domains as $domain) {
      $this->assertText($domain->label(), 'Domain form item found.');
    }
    $this->assertText($label, 'All affiliates field found.');
  }

}
