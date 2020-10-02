<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record entity reference field type.
 *
 * @group domain
 */
class DomainEntityReferenceTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'field', 'field_ui'];

  /**
   * Create, edit and delete a domain field via the user interface.
   */
  public function testDomainField() {
    $this->admin_user = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($this->admin_user);

    // Visit the article field administration page.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->assertSession()->statusCodeEquals(200);

    // Check for a domain field.
    $this->assertSession()->pageTextNotContains('Domain test field');

    // Visit the article field display administration page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->statusCodeEquals(200);

    // Check for a domain field.
    $this->assertSession()->pageTextNotContains('Domain test field');

    // Create test domain field.
    $this->domainCreateTestField();

    // Visit the article field administration page.
    $this->drupalGet('admin/structure/types/manage/article/fields');

    // Check the new field.
    $this->assertSession()->pageTextContains('Domain test field');

    // Visit the article field display administration page.
    $this->drupalGet('admin/structure/types/manage/article/display');

    // Check the new field.
    $this->assertSession()->pageTextContains('Domain test field');
  }

  /**
   * Create content for a domain field.
   */
  public function testDomainFieldStorage() {
    $this->admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create test domain field.
    $this->domainCreateTestField();

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Visit the article field display administration page.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check the new field exists on the page.
    $this->assertSession()->pageTextContains('Domain test field');

    // We expect to find 5 domain options.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $string = 'value="' . $domain->id() . '"';
      $this->assertSession()->responseContains($string);
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
    $edit["field_domain[{$one}]"] = TRUE;
    $edit["field_domain[{$two}]"] = TRUE;
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
    $values = $node->get('field_domain');

    // Get the expected value count.
    $this->assertCount(2, $values, 'Node saved with two domain records.');

  }

  /**
   * Creates a simple field for testing on the article content type.
   *
   * Note: This code is a model for auto-creation of fields.
   */
  public function domainCreateTestField() {
    $label = 'domain';
    $name = 'field_' . $label;

    $storage = [
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => [
        'target_type' => 'domain',
      ],
    ];
    $field_storage_config = \Drupal::entityTypeManager()->getStorage('field_storage_config')->create($storage);
    $field_storage_config->save();

    $field = [
      'field_name' => $name,
      'entity_type' => 'node',
      'label' => 'Domain test field',
      'bundle' => 'article',
      'settings' => [
        'handler_settings' => [
          'sort' => ['field' => 'weight', 'direction' => 'ASC'],
        ],
      ],
    ];
    $field_config = \Drupal::entityTypeManager()->getStorage('field_config')->create($field);
    $field_config->save();

    // Tell the form system how to behave.
    if ($display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.article.default')) {
      $display->setComponent($name, ['type' => 'options_buttons'])->save();
    }
  }

}
