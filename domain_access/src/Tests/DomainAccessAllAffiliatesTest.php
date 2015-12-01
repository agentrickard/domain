<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessAllAffiliatesTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain access entity reference field type.
 *
 * @group domain_access
 */
class DomainAccessAllAffiliatesTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui');

  function setUp() {
    parent::setUp();

    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();
  }

  /**
   * Tests that the module installed its field correctly.
   */
  function testDomainAccessAllField() {
    $label = 'Send to all affiliates';
    $this->admin_user = $this->drupalCreateUser(array('administer content types', 'administer node fields', 'administer node display', 'administer domains'));
    $this->drupalLogin($this->admin_user);

    // Visit the article field administration page.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->assertResponse(200, 'Manage fields page accessed.');

    // Check for the field.
    $this->assertText($label, 'Domain form field found.');

    // Visit the article field display administration page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertResponse(200, 'Manage field display page accessed.');

    // Check for the field.
    $this->assertText($label, 'Domain form field found.');
  }

  /**
   * Tests the storage of the domain access field.
   */
  function testDomainAccessAllFieldStorage() {
    $label = 'Send to all affiliates';
    $this->admin_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer node fields', 'administer node display', 'administer domains', 'publish to any domain'));
    $this->drupalLogin($this->admin_user);

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Visit the article field display administration page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200);

    // Check the new field exists on the page.
    $this->assertText($label, 'Found the domain field instance.');

    // We expect to find 5 domain options.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      $this->assertRaw($string, format_string('Found the %domain option.', array('%domain' => $domain->label())));
      if (!isset($one)) {
        $one = $domain->id();
        continue;
      }
      if (!isset($two)) {
        $two = $domain->id();
      }
    }

    // Try to post a node, assigned to the first two domains.
    $edit['title[0][value]'] = 'Test node';
    $edit["field_domain_access[{$one}]"] = TRUE;
    $edit["field_domain_access[{$two}]"] = TRUE;
    $edit["field_domain_all_affiliates[value]"] = 1;
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertResponse(200);
    $node = \Drupal::entityManager()->getStorage('node')->load(1);
    // Check that two values are set.
    $values = domain_access_get_entity_values($node);
    $this->assertTrue(count($values) == 2, 'Node saved with two domain records.');
    // Check that all affiliates is set.
    $this->assertTrue(!empty($node->get(DOMAIN_ACCESS_ALL_FIELD)->value), 'Node assigned to all affiliates.');
  }

}