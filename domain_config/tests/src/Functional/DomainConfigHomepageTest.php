<?php

namespace Drupal\Tests\domain_config\Functional;

use Drupal\user\RoleInterface;

/**
 * Tests the domain config system handling of home page routes.
 *
 * @group domain_config
 */
class DomainConfigHomepageTest extends DomainConfigTestBase {

  /**
   * @var array The modules to enable.
   */
  public static $modules = ['node', 'views'];

  /**
   * Tests that domain-specific homepage loading works.
   */
  public function testDomainConfigHomepage() {
    // Let anon users see content.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access content']);

    // Configure 'node' as front page.
    $site_config = $this->config('system.site');
    $site_config->set('page.front', '/node')->save();

    // No domains should exist.
    $this->domainTableIsEmpty();
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(5);
    // Get the domain list.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $node1 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Node 1',
      'promoted' => TRUE,
    ]);
    $node2 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Node 2',
      'promoted' => TRUE,
    ]);
    $node3 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Node 3',
      'promoted' => TRUE,
    ]);
    $homepages = $this->getHomepages();
    foreach ($domains as $domain) {
      foreach (['en', 'es'] as $langcode) {
        $prefix = '';
        if ($langcode == 'es') {
          $prefix = 'es/';
        }
        $home = $this->drupalGet($domain->getPath() . $prefix);

        // Check if this setting is picked up.
        $expected = $domain->getPath() . $prefix . $homepages[$domain->id()][$langcode];
        $expected_home = $this->drupalGet($expected);

        $this->assertEqual($home, $expected_home, 'Proper home page loaded (' . $domain->id() . ').');
      }
    }
    // Explicit test for https://www.drupal.org/project/domain/issues/3154402
    // Create and login user.
    $admin_user = $this->drupalCreateUser(['bypass node access', 'access administration pages']);
    $this->drupalLogin($admin_user);
    $this->drupalGet($domain->getPath() . 'node/' . $node3->id() . '/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->drupalLogout();

    // Retest the homepages.
    foreach ($domains as $domain) {
      foreach (['en', 'es'] as $langcode) {
        $prefix = '';
        if ($langcode == 'es') {
          $prefix = 'es/';
        }
        // Prime the cache to prevent a bigpipe mismatch.
        $this->drupalGet($domain->getPath() . $prefix);
        $home = $this->drupalGet($domain->getPath() . $prefix);

        // Check if this setting is picked up.
        $expected = $domain->getPath() . $prefix . $homepages[$domain->id()][$langcode];
        $expected_home = $this->drupalGet($expected);

        $this->assertEqual($home, $expected_home, 'Proper home page loaded (' . $domain->id() . ').');
      }
    }
  }

  /**
   * Returns the expected homepage paths for each domain.
   */
  private function getHomepages() {
    $homepages = [
      'example_com' => ['en' => 'node', 'es' => 'node'],
      'one_example_com' => ['en' => 'node/1', 'es' => 'node'],
      'two_example_com' => ['en' => 'node', 'es' => 'node'],
      'three_example_com' => ['en' => 'node', 'es' => 'node'],
      'four_example_com' => ['en' => 'node/2', 'es' => 'node/2'],
    ];
    return $homepages;
  }

}
