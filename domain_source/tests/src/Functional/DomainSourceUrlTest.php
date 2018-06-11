<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the rewriting links using core URL methods.
 *
 * @group domain_source
 */
class DomainSourceUrlTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_source', 'field', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 2 domains.
    DomainTestBase::domainCreateTestDomains(2);
  }

  /**
   * Tests domain source URLs.
   */
  public function testDomainSourceUrls() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';

    $nodes_values = [
      'type' => 'page',
      'title' => 'foo',
      DOMAIN_SOURCE_FIELD => $id,
    ];
    $node = $this->createNode($nodes_values);

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
  }

}
