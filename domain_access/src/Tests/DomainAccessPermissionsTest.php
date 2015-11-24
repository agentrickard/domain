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
  function testNodeAccess() {
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create page content', 'edit any page content', 'delete any page content'));
    $node1 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeCreateAccess($node1->bundle(), FALSE, $web_user1);
    $this->assertNodeAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $node1, $web_user1);

    // Ensures user with 'bypass node access' permission can do everything.
    $web_user2 = $this->drupalCreateUser(array('bypass node access'));
    $node2 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeCreateAccess($node2->bundle(), TRUE, $web_user2);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node2, $web_user2);

    // User cannot 'view own unpublished content'.
    $web_user3 = $this->drupalCreateUser(array('access content'));
    $node3 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user3->id()));
    $this->assertNodeAccess(array('view' => FALSE), $node3, $web_user3);

    // User cannot create content without permission.
    $this->assertNodeCreateAccess($node3->bundle(), FALSE, $web_user3);

    // User can 'view own unpublished content', but another user cannot.
    $web_user4 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $web_user5 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $node4 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user4->id()));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE), $node4, $web_user4);
    $this->assertNodeAccess(array('view' => FALSE), $node4, $web_user5);

    // Tests the default access provided for a published node.
    $node5 = $this->drupalCreateNode();
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node5, $web_user3);

    // Tests the "edit any BUNDLE" and "delete any BUNDLE" permissions.
    $web_user6 = $this->drupalCreateUser(array('access content', 'edit any page content', 'delete any page content'));
    $node6 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node6, $web_user6);

    // Tests the "edit own BUNDLE" and "delete own BUNDLE" permission.
    $web_user7 = $this->drupalCreateUser(array('access content', 'edit own page content', 'delete own page content'));
    // User should not be able to edit or delete nodes they do not own.
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node6, $web_user7);

    // User should be able to edit or delete nodes they own.
    $node7 = $this->drupalCreateNode(array('type' => 'page', 'uid' => $web_user7->id()));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node7, $web_user7);
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
   * Asserts that node create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The node bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertNodeCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessHandler->createAccess($bundle, $account, array(
      'langcode' => $langcode,
    )), $this->nodeAccessAssertMessage('create', $result, $langcode));
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
