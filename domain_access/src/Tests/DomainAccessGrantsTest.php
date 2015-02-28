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
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui');

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

    // Test the response of the node on each site. Should allow access.
    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath() . 'node/' . $node1->id());
      $this->assertRaw($node1->getTitle(), 'Loaded the proper domain.');
    }
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create article content', 'edit any article content', 'delete any article content'));
    $this->assertNodeAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $node1, $web_user1);
    // Grant access content and the user can view the node.
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('access content'));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node1, $web_user1);
    // Check global update and delete grants.
    // We have to use a new user here because the access check is cached.
    $web_user2 = $this->drupalCreateUser(array('access content', 'create article content', 'edit any article content', 'delete any article content'));
    $this->addDomainToEntity('user', $web_user2->id(), $active_domain, DOMAIN_ACCESS_USER_FIELD);
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('edit domain content', 'delete domain content'));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node1, $web_user2);
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
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertNodeAccess(array $ops, $node, AccountInterface $account, $langcode = NULL) {
    foreach ($ops as $op => $result) {
      if (empty($langcode)) {
        $langcode = $node->prepareLangcode();
      }
      $this->assertEqual($result, $this->accessHandler->access($node, $op, $langcode, $account), $this->nodeAccessAssertMessage($op, $result, $langcode));
    }
  }

  /**
   * Constructs an assert message for checking node access.
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
