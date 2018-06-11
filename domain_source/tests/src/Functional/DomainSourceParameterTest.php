<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for URLs that include query parameters.
 *
 * @group domain_source
 */
class DomainSourceParameterTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_source', 'domain_source_test', 'field', 'node', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 3 domains.
    DomainTestBase::domainCreateTestDomains(3);
  }

  public function testDomainSourceUrls() {
    // Create a node, assigned to a source domain.
    $id = 'example_com';

    $node = $this->createNode(['type' => 'page', 'title' => 'foo', DOMAIN_SOURCE_FIELD => $id]);

    // Variables for our tests.
    $path = 'domain-format-test';
    $options = ['query' => ['_format' => 'json']];
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath() . $path, $options);
    }
    $source = $domains[$id];
    $uri_path = '/' . $path;
    $expected = $uri_path . '?_format=json';

    // Get the link using Url::fromUserInput()
    $url = URL::fromUserInput($uri_path, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUserInput: ' . $url . ' expected: ' . $expected);
  }

}
