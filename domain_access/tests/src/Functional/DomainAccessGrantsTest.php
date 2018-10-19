<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests the application of domain access grants.
 *
 * @group domain_access
 */
class DomainAccessGrantsTest extends DomainTestBase {

  /**
   * The Entity access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_access', 'field', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure node_access table is clear.
    Database::getConnection()->delete('node_access')->execute();
  }

  /**
   * Creates a node and tests the creation of node access rules.
   */
  public function testDomainAccessGrants() {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Create 5 domains.
    $this->domainCreateTestDomains(5);
    // Assign a node to a random domain.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $active_domain = array_rand($domains, 1);
    $domain = $domains[$active_domain];
    // Create an article node.
    $node1 = $this->drupalCreateNode([
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => [$domain->id()],
    ]);
    $this->assertTrue($node_storage->load($node1->id()), 'Article node created.');

    // Test the response of the node on each site. Should allow access only to
    // the selected site.
    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'node/' . $node1->id();
      $this->drupalGet($path);
      if ($domain->id() == $active_domain) {
        $this->assertResponse(200);
        $this->assertRaw($node1->getTitle(), 'Article found on domain.');
      }
      else {
        $this->assertResponse(403);
      }
    }

    // Create an article node.
    $node2 = $this->drupalCreateNode([
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => [$domain->id()],
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ]);
    $this->assertTrue($node_storage->load($node2->id()), 'Article node created.');
    // Test the response of the node on each site. Should allow access on all.
    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'node/' . $node2->id();
      $this->drupalGet($path);
      $this->assertResponse(200);
      $this->assertRaw($node2->getTitle(), 'Article found on domain.');
    }
  }

}
