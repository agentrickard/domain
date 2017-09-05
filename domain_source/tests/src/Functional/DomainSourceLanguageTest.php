<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the rewriting links using core URL methods.
 *
 * @group domain_source
 */
class DomainSourceLanguageTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'domain', 'domain_source', 'field', 'node', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 5 domains.
    DomainTestBase::domainCreateTestDomains(3);

    // Add Hungarian and Afrikaans.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable content translation for the current entity type.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
  }

  public function domainSourceLanguageTest() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';
    // Create one node in Hungarian and marked as private.
    $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'status' => 1,
      DOMAIN_SOURCE_FIELD => $id,
    ]);

    // Programmatically create a translation.
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    // Reload the node.
    $node = $storage->load(1);
    // Create an Afrikaans translation.
    $id = 'two_example_com';
    $translation = $node->addTranslation('af');
    $translation->title->value = $this->randomString();
    $translation->{DOMAIN_SOURCE_FIELD}->value = $id2;
    $translation->status = 1;
    $node->save();
  }

}
