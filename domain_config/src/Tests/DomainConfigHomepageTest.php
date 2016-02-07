<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainConfigHomepageTest.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain_config\Tests\DomainConfigTestBase;

/**
 * Tests the domain config system handling of home page routes.
 *
 * @group domain_config
 */
class DomainConfigHomepageTest extends DomainConfigTestBase {

  public static $modules = array('node', 'views');

  /**
   * Tests that domain-specific homepage loading works.
   *
   * @TODO: Requires https://www.drupal.org/node/2662196
   */
  function testDomainConfigHomepage() {
    // Let anon users see content.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access content'));

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();

    // No domains should exist.
    $this->domainTableIsEmpty();
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(5);
    // Get the domain list.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    $node1 = $this->drupalCreateNode(array(
      'type' => 'article',
      'title' => 'Node 1',
      'promoted' => TRUE,
    ));
    $node2 = $this->drupalCreateNode(array(
      'type' => 'article',
      'title' => 'Node 2',
      'promoted' => TRUE,
    ));
    $homepages = $this->getHomepages();
    foreach ($domains as $domain) {
      $home = $this->drupalGet($domain->getPath());
      $expected = $domain->getPath() . $homepages[$domain->id()];
      $expected_home = $this->drupalGet($expected);
      $this->assertTrue($home == $expected_home, 'Proper home page loaded.');
    }
  }

  /**
   * Returns the expected homepage paths for each domain.
   */
  private function getHomepages() {
    $homepages = array(
      'example_com' => 'node',
      'one_example_com' => 'node/1',
      'two_example_com' => 'node',
      'three_example_com' => 'node',
      'four_example_com' => 'node/2',
    );
    return $homepages;
  }

}
