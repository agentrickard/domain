<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
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
  public static $modules = [
    'language',
    'content_translation',
    'domain',
    'domain_source',
    'field',
    'node',
    'user',
  ];

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

  /**
   * Tests domain source language.
   */
  public function testDomainSourceLanguage() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';
    // Create one node with no language.
    $node = $this->drupalCreateNode([
      'body' => [[]],
      'status' => 1,
      DOMAIN_SOURCE_FIELD => $id,
    ]);

    // Programmatically create a translation.
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    // Reload the node.
    $node = $storage->load(1);
    // Create an Afrikaans translation assigned to domain 2.
    $id2 = 'two_example_com';
    $translation = $node->addTranslation('af');
    $translation->title->value = $this->randomString();
    $translation->{DOMAIN_SOURCE_FIELD} = $id2;
    $translation->status = 1;
    $node->save();

    // Variables for our tests.
    $path = 'node/1';
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $source = $domains[$id];
    $expected = $source->getPath() . $path;
    $route_name = 'entity.node.canonical';
    $route_parameters = ['node' => 1];
    $uri = 'entity:' . $path;
    $uri_path = '/' . $path;
    $options = [];

    // Get the link using Url::fromRoute().
    $url = URL::fromRoute($route_name, $route_parameters, $options)->toString();
    $this->assertTrue($url == $expected, 'fromRoute');

    // Get the link using Url::fromUserInput()
    $url = URL::fromUserInput($uri_path, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUserInput');

    // Get the link using Url::fromUri()
    $url = URL::fromUri($uri, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUri');

    // Now test the same for the Arfrikaans translation.
    $path = 'node/1';
    $source = $domains[$id2];
    $expected = $source->getPath() . 'af/' . $path;
    $route_name = 'entity.node.canonical';
    $route_parameters = ['node' => 1];
    $uri = 'entity:' . $path;
    $uri_path = '/' . $path;
    $language = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('af');
    $options = ['language' => $language];

    $translation = $node->getTranslation('af');
    $this->assertTrue(domain_source_get($translation) == $id2, domain_source_get($translation));

    // Because of path cache, we have to flush here.
    drupal_flush_all_caches();

    // Get the link using Url::fromRoute().
    $url = URL::fromRoute($route_name, $route_parameters, $options)->toString();
    $this->assertTrue($url == $expected, 'fromRoute');

    // Get the link using Url::fromUserInput()
    $url = URL::fromUserInput($uri_path, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUserInput');

    // Get the link using Url::fromUri()
    $url = URL::fromUri($uri, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUri');
  }

}
