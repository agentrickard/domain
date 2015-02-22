<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessGrantsTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;

/**
 * Tests the application of domain access grants.
 *
 * @group domain_access
 */
class DomainAccessGrantsTest extends DomainTestBase {

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
  function testDomainAccessGrants() {
    // Create 5 domains.
    $this->domainCreateTestDomains(5);
    // Assign a node to a random domain.
    $domains = domain_load_multiple();
    $active_domain = array_rand($domains, 1);
    $domain = $domains[$active_domain];
    // Create an article node.
    $node1 = $this->drupalCreateNode(array(
      'type' => 'article',
      DOMAIN_ACCESS_NODE_FIELD => array($domain->id()),
    ));
    $this->assertTrue(entity_load('node', $node1->id()), 'Article node created.');

    debug($domain->id());
  }

}
