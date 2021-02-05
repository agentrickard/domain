<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests page caching results.
 *
 * @group domain_access
 */
class DomainAccessCacheTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_access',
  ];

  /**
   * Tests that a domain response is proper.
   */
  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains(5);
    $expected = [];

    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(NULL, TRUE);
    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath());
      // The page cache includes a colon at the end.
      $expected[] = $domain->getPath() . ':';
    }

    $database = \Drupal::database();
    $query = $database->query("SELECT cid FROM {cache_page}");
    $result = $query->fetchCol();

    $this->assertEqual(sort($expected), sort($result), 'Cache returns as expected.');

    // Now create a node and test the cache.
    // Create an article node assigned to two domains.
    $ids = ['example_com', 'four_example_com'];
    $node1 = $this->drupalCreateNode([
      'type' => 'article',
      'field_domain_access' => [$ids],
      'path' => '/test'
    ]);

    $original = $expected;

    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath() . 'test');
      // The page cache includes a colon at the end.
      $expected[] = $domain->getPath() . 'test:';
    }

    $query = $database->query("SELECT cid FROM {cache_page}");
    $result = $query->fetchCol();

    $this->assertEqual(sort($expected), sort($result), 'Cache returns as expected.');

    // When we delete the node, we want all cids removed.
    $node1->delete();

    $query = $database->query("SELECT cid FROM {cache_page}");
    $result = $query->fetchCol();

    $this->assertEqual(sort($original), sort($result), 'Cache returns as expected.');

  }

}
