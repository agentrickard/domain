<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;
use Drupal\domain_source\DomainSourceElementManagerInterface;

/**
 * Tests behavior for excluding some links from rewriting.
 *
 * @group domain_source
 */
class DomainSourceExcludeTest extends DomainTestBase {

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
   * Tests domain source excludes.
   */
  public function testDomainSourceExclude() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';

    $node_values = [
      'type' => 'page',
      'title' => 'foo',
      DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD => $id,
    ];
    $node = $this->createNode($node_values);

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
    $url = Url::fromRoute($route_name, $route_parameters, $options)->toString();
    $this->assertEquals($expected, $url, 'fromRoute');

    // Get the link using Url::fromUserInput()
    $url = Url::fromUserInput($uri_path, $options)->toString();
    $this->assertEquals($expected, $url, 'fromUserInput');

    // Get the link using Url::fromUri()
    $url = Url::fromUri($uri, $options)->toString();
    $this->assertEquals($expected, $url, 'fromUri');

    // Exclude the edit path from rewrites.
    $config = $this->config('domain_source.settings');
    $config->set('exclude_routes', ['edit_form' => 'edit_form'])->save();

    // Variables for our tests.
    $path = 'node/1/edit';
    $expected = base_path() . $path;
    $route_name = 'entity.node.edit_form';
    $route_parameters = ['node' => 1];
    $uri = 'internal:/' . $path;
    $uri_path = '/' . $path;
    $options = [];

    // Because of path cache, we have to flush here.
    drupal_flush_all_caches();

    // Get the link using Url::fromRoute().
    $url = Url::fromRoute($route_name, $route_parameters, $options)->toString();
    $this->assertEquals($expected, $url, 'fromRoute');

    // Get the link using Url::fromUserInput()
    $url = Url::fromUserInput($uri_path, $options)->toString();
    $this->assertEquals($expected, $url, 'fromUserInput');

    // Get the link using Url::fromUri()
    $url = Url::fromUri($uri, $options)->toString();
    $this->assertEquals($expected, $url, 'fromUri');
  }

}
