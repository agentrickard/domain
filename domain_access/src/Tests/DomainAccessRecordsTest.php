<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessRecordsTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;

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
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui');

  function setUp() {
    parent::setUp();
    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();
  }

  /**
   * Creates a node and tests the creation of node access rules.
   */
  function testDomainAccessRecords() {
    // Create 5 domains.
    $this->domainCreateTestDomains(5);
    // Assign a node to a random domain.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    $active_domain = array_rand($domains, 1);
    $domain = $domains[$active_domain];
    // Create an article node.
    $node1 = $this->drupalCreateNode(array(
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => array($domain->id()),
      DOMAIN_ACCESS_ALL_FIELD => 0,
    ));
    $this->assertTrue(\Drupal::entityManager()->getStorage('node')->load($node1->id()), 'Article node created.');

    // Check to see if grants added by domain_node_access_records made it in.
    $records = db_query('SELECT realm, gid, grant_view, grant_update, grant_delete FROM {node_access} WHERE nid = :nid', array(':nid' => $node1->id()))->fetchAll();
    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'domain_id', 'Grant with domain_id acquired for node.');
    $this->assertEqual($records[0]->gid, $domain->getDomainId(), 'Grant with proper id acquired for node.');
    $this->assertEqual($records[0]->grant_view, 1, 'Grant view stored.');
    $this->assertEqual($records[0]->grant_update, 1, 'Grant update stored.');
    $this->assertEqual($records[0]->grant_delete, 1, 'Grant delete stored.');

    // Create another article node.
    $node2 = $this->drupalCreateNode(array(
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => array($domain->id()),
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ));
    $this->assertTrue(\Drupal::entityManager()->getStorage('node')->load($node2->id()), 'Article node created.');
    // Check to see if grants added by domain_node_access_records made it in.
    $records = db_query('SELECT realm, gid, grant_view, grant_update, grant_delete FROM {node_access} WHERE nid = :nid ORDER BY realm', array(':nid' => $node2->id()))->fetchAll();
    $this->assertEqual(count($records), 2, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'domain_id', 'Grant with domain_id acquired for node.');
    $this->assertEqual($records[0]->gid, $domain->getDomainId(), 'Grant with proper id acquired for node.');
    $this->assertEqual($records[0]->grant_view, 1, 'Grant view stored.');
    $this->assertEqual($records[0]->grant_update, 1, 'Grant update stored.');
    $this->assertEqual($records[0]->grant_delete, 1, 'Grant delete stored.');
    $this->assertEqual($records[1]->realm, 'domain_site', 'Grant with domain_site acquired for node.');
    $this->assertEqual($records[1]->gid, 0, 'Grant with proper id acquired for node.');
    $this->assertEqual($records[1]->grant_view, 1, 'Grant view stored.');
    $this->assertEqual($records[1]->grant_update, 0, 'Grant update stored.');
    $this->assertEqual($records[1]->grant_delete, 0, 'Grant delete stored.');

  }

}
