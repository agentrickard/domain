<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests the domain source entity reference field type.
 *
 * @group domain_source
 */
class DomainSourceEntityReferenceTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'domain_source',
    'field',
    'field_ui',
    'menu_ui',
    'block',
  ];

  /**
   * Tests that the module installed its field correctly.
   */
  public function testDomainSourceNodeField() {
    $this->admin_user = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($this->admin_user);

    // Visit the article field administration page.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->assertResponse(200, 'Manage fields page sourceed.');

    // Check for a domain field.
    $this->assertText('Domain Source', 'Domain form field found.');

    // Visit the article field display administration page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertResponse(200, 'Manage field display page sourceed.');

    // Check for a domain field.
    $this->assertText('Domain Source', 'Domain form field found.');
  }

  /**
   * Tests the storage of the domain source field.
   */
  public function testDomainSourceFieldStorage() {
    $this->admin_user = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'administer menu',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Visit the article field display administration page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200);

    // Check the new field exists on the page.
    $this->assertText('Domain Source', 'Found the domain field instance.');

    // We expect to find 5 domain options + none.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      $this->assertRaw($string, 'Found the domain option.');
      if (!isset($one)) {
        $one = $domain->id();
        continue;
      }
      if (!isset($two)) {
        $two = $domain->id();
        $two_path = $domain->getPath();
      }
    }
    $this->assertRaw('value="_none"', 'Found the _none_ option.');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Try to post a node, assigned to the second domain.
    $edit['title[0][value]'] = 'Test node';
    $edit['field_domain_source'] = $two;
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertResponse(200);
    $node = $node_storage->load(1);
    // Check that the value is set.
    $value = domain_source_get($node);
    $this->assertTrue($value == $two, 'Node saved with proper source record.');

    // Test the URL.
    $url = $node->toUrl()->toString();
    $expected_url = $two_path . 'node/1';
    $this->assertTrue($expected_url == $url, 'URL rewritten correctly.');

    // Try to post a node, assigned to no domain.
    $edit['title[0][value]'] = 'Test node';
    $edit["field_domain_source"] = '_none';
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertResponse(200);
    $node = $node_storage->load(2);
    // Check that the value is set.
    $value = domain_source_get($node);
    $this->assertNull($value, 'Node saved with proper source record.');

    // Test the url.
    $url = $node->toUrl()->toString();
    $expected_url = base_path() . 'node/2';
    $this->assertTrue($expected_url == $url, 'URL rewritten correctly.');

    // Place the menu block.
    $this->drupalPlaceBlock('system_menu_block:main');

    // Enable main menu as available menu.
    $edit = [
      'menu_options[main]' => 1,
      'menu_parent' => 'main:',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));

    // Create a third node that is assigned to a menu.
    $edit = [
      'title[0][value]' => 'Node 3',
      'menu[enabled]' => 1,
      'menu[title]' => 'Test preview',
      'field_domain_source' => $two,
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    // Test the URL against expectations, and the rendered menu link.
    $node = $node_storage->load(3);
    $url = $node->toUrl()->toString();
    $expected_url = $two_path . 'node/3';
    $this->assertTrue($expected_url == $url, 'URL rewritten correctly.');
    // Load the page with a menu and check that link.
    $this->drupalGet('node/3');
    $this->assertRaw('href="' . $url, 'Menu link rewritten correctly.');

    // Remove the field from the node type and make sure nothing breaks.
    // See https://www.drupal.org/node/2892612
    $id = 'node.article.field_domain_source';
    if ($field = \Drupal::entityManager()->getStorage('field_config')->load($id)) {
      $field->delete();
      field_purge_batch(10, $field->uuid());
      drupal_flush_all_caches();
    }
    // Visit the article field display administration page.
    $this->drupalGet('node/add/article');
    $this->assertResponse(200);
    // Try to post a node, assigned to no domain.
    $edit2['title[0][value]'] = 'Test node';
    $this->drupalPostForm('node/add/article', $edit2, 'Save');
    // Test the URL against expectations, and the rendered menu link.
    $node = $node_storage->load(4);
    $url = $node->toUrl()->toString();
    $expected_url = '/node/4';
    $this->assertTrue($expected_url == $url, 'No URL rewrite performed.');
  }

}
