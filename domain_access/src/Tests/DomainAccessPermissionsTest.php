<?php

/**
 * @file
 * Definition of Drupal\domain_access\Tests\DomainAccessPermissionsTest
 */

namespace Drupal\domain_access\Tests;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\domain\DomainInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the domain access integration with node_access callbacks.
 *
 * @group domain_access
 */
class DomainAccessPermissionsTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'node');

  public function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', array())->save();
    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ));
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // Create 5 domains.
    $this->domainCreateTestDomains(5);

    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();
  }

  /**
   * Runs basic tests for node_access function.
   */
  function testDomainAccessPermissions() {
    // Note that these are hook_node_access() rules. Node Access system tests
    // are in DomainAccessRecordsTest.

    // We expect to find 5 domain options. Set two for later use.
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    foreach ($domains as $domain) {
      if (!isset($one)) {
        $one = $domain->id();
        continue;
      }
      if (!isset($two)) {
        $two = $domain->id();
      }
    }
    // Assign our user to domain $two. Test on $one and $two.
    $domain_user1 = $this->drupalCreateUser(array('access content', 'edit domain content', 'delete domain content'));
    $this->addDomainToEntity('user', $domain_user1->id(), $two, DOMAIN_ACCESS_USER_FIELD);
    $domain_user1 = \Drupal::entityManager()->getStorage('user')->load($domain_user1->id());
    $assigned = domain_access_get_entity_values($domain_user1, DOMAIN_ACCESS_USER_FIELD);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');

    // Assign one node to default domain, and one to our test domain.
    $domain_node1 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_NODE_FIELD => [$one]));
    $domain_node2 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_NODE_FIELD => [$two]));
    $assigned = domain_access_get_entity_values($domain_node1, DOMAIN_ACCESS_NODE_FIELD);
    $this->assertTrue(isset($assigned[$one]), 'Node1 assigned to proper test domain.');
    $assigned = domain_access_get_entity_values($domain_node2, DOMAIN_ACCESS_NODE_FIELD);
    $this->assertTrue(isset($assigned[$two]), 'Node2 assigned to proper test domain.');

    // Tests 'edit domain content' to edit content assigned to their domains.
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $domain_node1, $domain_user1);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $domain_node2, $domain_user1);

    // Tests 'edit domain TYPE content'.
    // Assign our user to domain $two. Test on $one and $two.
    $domain_user3 = $this->drupalCreateUser(array('access content', 'update page content on assigned domains', 'delete page content on assigned domains'));
    $this->addDomainToEntity('user', $domain_user3->id(), $two, DOMAIN_ACCESS_USER_FIELD);
    $domain_user3 = \Drupal::entityManager()->getStorage('user')->load($domain_user3->id());
    $assigned = domain_access_get_entity_values($domain_user3, DOMAIN_ACCESS_USER_FIELD);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');

    // Assign two different node types to our test domain.
    $domain_node3 = $this->drupalCreateNode(array('type' => 'article', DOMAIN_ACCESS_NODE_FIELD => [$two]));
    $domain_node4 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_NODE_FIELD => [$two]));
    $assigned = domain_access_get_entity_values($domain_node3, DOMAIN_ACCESS_NODE_FIELD);
    $this->assertTrue(isset($assigned[$two]), 'Node3 assigned to proper test domain.');
    $assigned = domain_access_get_entity_values($domain_node4, DOMAIN_ACCESS_NODE_FIELD);
    $this->assertTrue(isset($assigned[$two]), 'Node4 assigned to proper test domain.');

    // Tests 'edit TYPE content on assigned domains.'
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $domain_node3, $domain_user3);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $domain_node4, $domain_user3);

    // Tests create permissions. Any content on assigned domains.
    $domain_user4 = $this->drupalCreateUser(array('access content', 'create domain content'));
    $this->addDomainToEntity('user', $domain_user4->id(), $two, DOMAIN_ACCESS_USER_FIELD);
    $domain_user4 = \Drupal::entityManager()->getStorage('user')->load($domain_user4->id());
    $assigned = domain_access_get_entity_values($domain_user4, DOMAIN_ACCESS_USER_FIELD);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');
    // This test is domain sensitive.
    foreach ($domains as $domain) {
      $this->domainLogin($domain, $domain_user4);
      $url = $domain->getPath() . '/node/add/page';
      $this->drupalGet($url);
      if ($domain->id() == $two) {
        $this->assertResponse(200);
      }
      else {
       $this->assertResponse(403);
      }
    }
    // Tests create permissions. Page content on assigned domains.
    $domain_user4 = $this->drupalCreateUser(array('access content', 'create page content on assigned domains'));
    $this->addDomainToEntity('user', $domain_user4->id(), $two, DOMAIN_ACCESS_USER_FIELD);
    $domain_user4 = \Drupal::entityManager()->getStorage('user')->load($domain_user4->id());
    $assigned = domain_access_get_entity_values($domain_user4, DOMAIN_ACCESS_USER_FIELD);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');
    // This test is domain sensitive.
    foreach ($domains as $domain) {
      $this->domainLogin($domain, $domain_user4);
      $url = $domain->getPath() . '/node/add/page';
      $this->drupalGet($url);
      if ($domain->id() == $two) {
        $this->assertResponse(200);
      }
      else {
       $this->assertResponse(403);
      }
      $url = $domain->getPath() . '/node/add/article';
      $this->drupalGet($url);
      $this->assertResponse(403);
    }

  }

  /**
   * Asserts that node access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected node access grants for the node
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\node\Entity\Node $node
   *   The node object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  function assertNodeAccess(array $ops, $node, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEqual($result, $this->accessHandler->access($node, $op, $account), $this->nodeAccessAssertMessage($op, $result));
    }
  }

  /**
   * Constructs an assert message to display which node access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the node access permission test that was performed.
   */
  function nodeAccessAssertMessage($operation, $result, $langcode = NULL) {
    return format_string(
      'Node access returns @result with operation %op, language code %langcode.',
      array(
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty'
      )
    );
  }

}
