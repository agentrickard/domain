<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessGrantsTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;
use Drupal\Core\Session\AccountInterface;

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
  public static $modules = array('domain', 'domain_access', 'field', 'node');

  function setUp() {
    parent::setUp();
    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();
    // Set the access handler.
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // Clear permissions for authenticated users.
    $this->config('user.role.' . DRUPAL_AUTHENTICATED_RID)->set('permissions', array())->save();
  }

  /**
   * Creates a node and tests the creation of node access rules.
   */
  function testDomainAccessGrants() {
    // The {node_access} table is not emptied properly by the setup.
    db_delete('node_access')->execute();
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
    ));
    $this->assertTrue(\Drupal::entityManager()->getStorage('node')->load($node1->id()), 'Article node created.');

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
    $node2 = $this->drupalCreateNode(array(
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => array($domain->id()),
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ));
    $this->assertTrue(\Drupal::entityManager()->getStorage('node')->load($node2->id()), 'Article node created.');
    // Test the response of the node on each site. Should allow access on all.
    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'node/' . $node2->id();
      $this->drupalGet($path);
      $this->assertResponse(200);
      $this->assertRaw($node2->getTitle(), 'Article found on domain.');
    }
  }

}
