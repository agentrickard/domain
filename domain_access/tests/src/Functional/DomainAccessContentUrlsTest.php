<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for getting all URLs for an entity.
 *
 * @group domain_access
 */
class DomainAccessContentUrlsTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_access', 'field', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 4 domains.
    DomainTestBase::domainCreateTestDomains(4);
  }

  /**
   * Tests domain source URLs.
   */
  public function testDomainContentUrls() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';

    $nodes_values = [
      'type' => 'page',
      'title' => 'foo',
      DOMAIN_ACCESS_FIELD => [
        'example_com',
        'one_example_com',
        'two_example_com',
      ],
      DOMAIN_ACCESS_ALL_FIELD => 0,
    ];
    $node = $this->createNode($nodes_values);

    // Variables for our tests.
    $path = 'node/1';
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $route_name = 'entity.node.canonical';
    $route_parameters = ['node' => 1];
    $uri = 'entity:' . $path;
    $uri_path = '/' . $path;
    $expected = $uri_path;
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

    // Get the path processor service.
    $paths = \Drupal::service('domain_access.manager');
    $urls = $paths->getContentUrls($node);
    $expected = [
      'example_com' => $domains['example_com']->getPath() . 'node/1',
      $id => $domains[$id]->getPath() . 'node/1',
      'two_example_com' => $domains['two_example_com']->getPath() . 'node/1',
    ];
    $this->assertTrue($expected == $urls);
  }

}
