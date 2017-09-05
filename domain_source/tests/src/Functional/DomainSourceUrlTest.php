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
  public static $modules = array('domain', 'domain_source', 'field', 'node', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 5 domains.
    DomainTestBase::domainCreateTestDomains(2);
  }

  public function testDomainSourceUrls() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';

    $node = $this->createNode(['type' => 'page', 'title' => 'foo', DOMAIN_SOURCE_FIELD => $id]);

    // Variables for our tests.
    $path = 'node/1';
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    $source = $domains[$id];
    $expected = $source->getPath() . $path;
    $route_name = 'entity.node.canonical';
    $route_parameters = ['node' => 1];
    $uri = 'entity:' . $path;
    $uri_path = '/' . $path;

    // Get the link using Url::fronRoute().
    $url = URL::fromRoute($route_name, $route_parameters)->toString();
    $this->assertTrue($url == $expected, 'fromRoute');

    // Get the link using Url::fromUserInput()
    $url = URL::fromUserInput($uri_path)->toString();
    $this->assertTrue($url == $expected, $url);

    // Get the link using Url::fromUri()
    $url = URL::fromUri($uri)->toString();
    $this->assertTrue($url == $expected, $url);
  }

}
