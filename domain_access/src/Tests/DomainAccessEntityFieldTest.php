<?php

namespace Drupal\domain_access\Tests;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the domain access entity reference field type for custom entities.
 *
 * @group domain_access
 */
class DomainAccessEntityFieldTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'domain_access_test', 'field', 'field_ui', 'user', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();

    // Create 5 domains.
    $this->domainCreateTestDomains(5);
  }

  /**
   * Tests that the fields are accessed properly.
   */
  public function testDomainAccessEntityFields() {
    $label = 'Send to all affiliates';
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Domain vocabulary',
      'description' => 'Test taxonomy for Domain Access',
      'vid' => 'domain_access',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => 100,
    ));
    $vocabulary->save();
    $text['taxonomy_term'] = [
      'name' => 'term',
      'label' => 'Send to all affiliates',
      'description' => 'Make this term available on all domains.',
    ];
    domain_access_confirm_fields('taxonomy_term', 'domain_access', $text);
    $this->admin_user = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'publish to any domain',
      'administer taxonomy',
      'administer taxonomy_term fields',
      'administer taxonomy_term form display',
    ));
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/structure/taxonomy/manage/domain_access/overview/fields');
    $this->assertResponse(200, 'Manage fields page accessed.');

    // Check for a domain field.
    $this->assertText('Domain Access', 'Domain form field found.');
  }

}
