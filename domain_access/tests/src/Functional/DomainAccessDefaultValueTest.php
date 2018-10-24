<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests the domain access handling of default field values.
 *
 * @see https://www.drupal.org/node/2779133
 *
 * @group domain_access
 */
class DomainAccessDefaultValueTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_access', 'field', 'field_ui'];

  /**
   * Test the usage of DomainAccessManager::getDefaultValue().
   */
  public function testDomainAccessDefaultValue() {
    $this->admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'publish to any domain',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Visit the article field display administration page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200);

    // Check the new field exists on the page.
    $this->assertText('Domain Access', 'Found the domain field instance.');
    $this->assertRaw('name="field_domain_access[example_com]" value="example_com" checked="checked"', 'Default domain selected.');
    // Check the all affiliates field.
    $this->assertText('Send to all affiliates', 'Found the all affiliates field instance.');
    $this->assertNoRaw('name="field_domain_all_affiliates[value]" value="1" checked="checked"', 'All affiliates not selected.');

    // Now save the node with the values set.
    $edit = [
      'title[0][value]' => 'Test node',
      'field_domain_access[example_com]' => 'example_com',
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    // Load the node.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
    $this->assertTrue($node, 'Article node created.');
    // Check that the values are set.
    $values = \Drupal::service('domain_access.manager')->getAccessValues($node);
    $this->assertTrue(count($values) == 1, 'Node saved with one domain record.');
    $allValue = \Drupal::service('domain_access.manager')->getAllValue($node);
    $this->assertTrue(empty($allValue), 'Not sent to all affiliates.');

    // Logout the admin user.
    $this->drupalLogout();

    // Create a limited value user.
    $this->test_user = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
    ]);

    // Login and try to edit the created node.
    $this->drupalLogin($this->test_user);

    $this->drupalGet('node/1/edit');
    $this->assertResponse(200);

    // Now save the node with the values set.
    $edit = [
      'title[0][value]' => 'Test node update',
    ];
    $this->drupalPostForm('node/1/edit', $edit, 'Save');

    // Load the node.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
    $this->assertTrue($node, 'Article node created.');
    // Check that the values are set.
    $values = \Drupal::service('domain_access.manager')->getAccessValues($node);
    $this->assertTrue(count($values) == 1, 'Node saved with one domain record.');
    $allValue = \Drupal::service('domain_access.manager')->getAllValue($node);
    $this->assertTrue(empty($allValue), 'Not sent to all affiliates.');

  }

}
