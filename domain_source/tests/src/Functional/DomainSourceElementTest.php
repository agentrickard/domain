<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the domain source field element.
 *
 * @group domain_source
 */
class DomainSourceElementTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'domain_access',
    'domain_source',
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
   * Test runner.
   */
  public function testDomainSourceElement() {
    $this->runInstalledTest('article');
    $node_type = $this->createContentType(['type' => 'test']);
    $this->runInstalledTest('test');
  }

  /**
   * Basic test setup.
   */
  public function runInstalledTest($node_type) {
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'publish to any domain',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $nid = $node_type == 'article' ? 1 : 2;

    // Set the title, so the node can be saved.
    $this->fillField('title[0][value]', 'Test node');

    // We expect to find 5 domain options. We set two as selected.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $count = 0;
    $ids = ['example_com', 'one_example_com', 'two_example_com'];
    foreach ($domains as $domain) {
      $locator = DOMAIN_ACCESS_FIELD . '[' . $domain->id() . ']';
      $this->findField($locator);
      if (in_array($domain->id(), $ids)) {
        $this->checkField($locator);
      }
    }
    // Find the all affiliates field.
    $locator = DOMAIN_ACCESS_ALL_FIELD . '[value]';
    $this->findField($locator);

    // Set all affiliates to TRUE.
    $this->checkField($locator);

    // Find the Domain Source field.
    $locator = DOMAIN_SOURCE_FIELD;
    $this->findField($locator);
    // Set it to one_example_com.
    $this->selectFieldOption($locator, 'one_example_com');

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Check the URL.
    $url = $this->geturl();
    $this->assert(strpos($url, 'node/' . $nid . '/edit') === FALSE, 'Form submitted.');

    // Edit the node.
    $this->drupalGet('node/' . $nid . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Set the domain source field to an unselected domain.
    $this->selectFieldOption($locator, 'three_example_com');

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->assertEscaped('The source domain must be selected as a publishing option.');

    // Check the URL.
    $url = $this->geturl();
    $this->assert(strpos($url, 'node/' . $nid . '/edit') > 0, 'Form not submitted.');

    // Set the field properly and save again.
    $this->selectFieldOption($locator, 'one_example_com');

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Check the URL.
    $url = $this->geturl();
    $this->assert(strpos($url, 'node/' . $nid . '/edit') === FALSE, 'Form submitted.');

    // Save with no source.
    // Edit the node.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Set the domain source field to an unselected domain.
    $this->selectFieldOption($locator, '_none');

    // Save the form.
    $this->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);

    // Check the URL.
    $url = $this->geturl();
    $this->assert(strpos($url, 'node/' . $nid . '/edit') === FALSE, 'Form submitted.');
  }

}
