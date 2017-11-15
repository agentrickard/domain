<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the rewriting links using core URL methods.
 *
 * @group domain_source
 */
class DomainSourceExcludeTest extends DomainTestBase {

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

    // Create 2 domains.
    DomainTestBase::domainCreateTestDomains(2);
  }

  public function testDomainSourceExclude() {
    // Create a node, assigned to a source domain.
    $id = 'one_example_com';

    $node = $this->createNode(['type' => 'page', 'title' => 'foo', DOMAIN_SOURCE_FIELD => $id]);

    // Variables for our tests.
    $path = 'node/1';
    $domains = \Drupal::service('entity_type.manager')->getStorage('domain')->loadMultiple();
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

    // Exclude the edit path from rewrites.
    $config = $this->config('domain_source.settings');
    $config->set('exclude_routes', ['edit_form' => 'edit_form'])->save();

    // Variables for our tests.
    $path = 'node/1/edit';
    $expected = '/' . $path;
    $route_name = 'entity.node.edit_form';
    $route_parameters = ['node' => 1];
    $uri = 'internal:/' . $path;
    $uri_path = '/' . $path;
    $options = [];

    // Because of path cache, we have to flush here.
    drupal_flush_all_caches();

    // Get the link using Url::fromRoute().
    $url = URL::fromRoute($route_name, $route_parameters, $options)->toString();
    $this->assertTrue($url == $expected, 'fromRoute' . $url);

    // Get the link using Url::fromUserInput()
    $url = URL::fromUserInput($uri_path, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUserInput');

    // Get the link using Url::fromUri()
    $url = URL::fromUri($uri, $options)->toString();
    $this->assertTrue($url == $expected, 'fromUri');
  }

}
