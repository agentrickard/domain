<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests the domain access integration with node_access records.
 *
 * @group domain_access
 */
class DomainAccessRecordsTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['domain', 'domain_access', 'field', 'field_ui'];

  /**
   * Creates a node and tests the creation of node access rules.
   */
  public function testDomainAccessRecords() {
    // Create 5 domains.
    $this->domainCreateTestDomains(5);
    // Assign a node to a random domain.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $active_domain = array_rand($domains, 1);
    $domain = $domains[$active_domain];
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Create an article node.
    $node1 = $this->drupalCreateNode([
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => [$domain->id()],
      DOMAIN_ACCESS_ALL_FIELD => 0,
    ]);
    $this->assertNotNull($node_storage->load($node1->id()), 'Article node created.');

    // Check to see if grants added by domain_node_access_records made it in.
    $query = 'SELECT realm, gid, grant_view, grant_update, grant_delete FROM {node_access} WHERE nid = :nid';
    $records = Database::getConnection()
      ->query($query, [':nid' => $node1->id()])
      ->fetchAll();

    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEquals('domain_id', $records[0]->realm, 'Grant with domain_id acquired for node.');
    $this->assertEquals($domain->getDomainId(), $records[0]->gid, 'Grant with proper id acquired for node.');
    $this->assertEquals(1, $records[0]->grant_view, 'Grant view stored.');
    $this->assertEquals(1, $records[0]->grant_update, 'Grant update stored.');
    $this->assertEquals(1, $records[0]->grant_delete, 'Grant delete stored.');

    // Create another article node.
    $node2 = $this->drupalCreateNode([
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => [$domain->id()],
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ]);
    $this->assertNotNull($node_storage->load($node2->id()), 'Article node created.');
    // Check to see if grants added by domain_node_access_records made it in.
    $query = 'SELECT realm, gid, grant_view, grant_update, grant_delete FROM {node_access} WHERE nid = :nid ORDER BY realm';
    $records = Database::getConnection()
      ->query($query, [':nid' => $node2->id()])
      ->fetchAll();
    $this->assertCount(2, $records, 'Returned the correct number of rows.');
    $this->assertEquals('domain_id', $records[0]->realm, 'Grant with domain_id acquired for node.');
    $this->assertEquals($domain->getDomainId(), $records[0]->gid, 'Grant with proper id acquired for node.');
    $this->assertEquals(1, $records[0]->grant_view, 'Grant view stored.');
    $this->assertEquals(1, $records[0]->grant_update, 'Grant update stored.');
    $this->assertEquals(1, $records[0]->grant_delete, 'Grant delete stored.');
    $this->assertEquals('domain_site', $records[1]->realm, 'Grant with domain_site acquired for node.');
    $this->assertEquals(0, $records[1]->gid, 'Grant with proper id acquired for node.');
    $this->assertEquals(1, $records[1]->grant_view, 'Grant view stored.');
    $this->assertEquals(0, $records[1]->grant_update, 'Grant update stored.');
    $this->assertEquals(0, $records[1]->grant_delete, 'Grant delete stored.');
  }

}
