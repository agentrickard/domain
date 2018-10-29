<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests saving the domain access field elements in multiple languages.
 *
 * @group domain_access
 */
class DomainAccessLanguageSaveTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'domain_access',
    'field',
    'user',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Add Hungarian and Afrikaans.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable content translation for the current entity type.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
  }

  /**
   * Basic test setup.
   */
  public function testDomainAccessSave() {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    // Save a node programmatically.
    $node = $storage->create([
      'type' => 'article',
      'title' => 'Test node',
      'uid' => '1',
      'status' => 1,
      DOMAIN_ACCESS_FIELD => ['example_com'],
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ]);
    $node->save();

    // Load the node.
    $node = $storage->load(1);

    // Check that two values are set properly.
    $manager = \Drupal::service('domain_access.manager');
    $values = $manager->getAccessValues($node);
    $this->assert(count($values) == 1, 'Node saved with one domain records.');
    $value = $manager->getAllValue($node);
    $this->assert($value == 1, 'Node saved to all affiliates.');

    // Create an Afrikaans translation assigned to domain 2.
    $translation = $node->addTranslation('af');
    $translation->title->value = $this->randomString();
    $translation->{DOMAIN_ACCESS_FIELD} = ['example_com', 'one_example_com'];
    $translation->{DOMAIN_ACCESS_ALL_FIELD} = 0;
    $translation->status = 1;
    $node->save();

    // Load and check the translated node.
    $parent_node = $storage->load(1);
    $node = $parent_node->getTranslation('af');
    $values = $manager->getAccessValues($node);
    $this->assert(count($values) == 2, 'Node saved with two domain records.');
    $value = $manager->getAllValue($node);
    $this->assert($value == 0, 'Node not saved to all affiliates.');
  }

}
