<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests page caching results.
 *
 * @group domain
 */
class DomainPageCacheTest extends DomainTestBase {

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

  }

}
