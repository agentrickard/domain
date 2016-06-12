<?php

namespace Drupal\domain_access\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\user\RoleInterface;

/**
 * Tests the domain access integration with node_access callbacks.
 *
 * @group domain_access
 */
class DomainAccessPermissionsTest extends DomainTestBase {

  /**
   * The Entity access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $accessHandler
   */
  protected $accessHandler;

  /**
   * The Domain Access manager.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface $manager
   */
  protected $manager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    $this->manager = \Drupal::service('domain_access.manager');
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
  public function testDomainAccessPermissions() {
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
    $domain_user1 = $this->drupalCreateUser(array(
      'access content',
      'edit domain content',
      'delete domain content',
    ));
    $this->addDomainToEntity('user', $domain_user1->id(), $two);
    $domain_user1 = \Drupal::entityManager()->getStorage('user')->load($domain_user1->id());
    $assigned = $this->manager->getAccessValues($domain_user1);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');

    // Assign one node to default domain, and one to our test domain.
    $domain_node1 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_FIELD => [$one]));
    $domain_node2 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_FIELD => [$two]));
    $assigned = $this->manager->getAccessValues($domain_node1);
    $this->assertTrue(isset($assigned[$one]), 'Node1 assigned to proper test domain.');
    $assigned = $this->manager->getAccessValues($domain_node2);
    $this->assertTrue(isset($assigned[$two]), 'Node2 assigned to proper test domain.');

    // Tests 'edit domain content' to edit content assigned to their domains.
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $domain_node1, $domain_user1);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $domain_node2, $domain_user1);

    // Tests 'edit domain TYPE content'.
    // Assign our user to domain $two. Test on $one and $two.
    $domain_user3 = $this->drupalCreateUser(array(
      'access content',
      'update page content on assigned domains',
      'delete page content on assigned domains',
    ));
    $this->addDomainToEntity('user', $domain_user3->id(), $two);
    $domain_user3 = \Drupal::entityManager()->getStorage('user')->load($domain_user3->id());
    $assigned = $this->manager->getAccessValues($domain_user3);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');

    // Assign two different node types to our test domain.
    $domain_node3 = $this->drupalCreateNode(array('type' => 'article', DOMAIN_ACCESS_FIELD => [$two]));
    $domain_node4 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_FIELD => [$two]));
    $assigned = $this->manager->getAccessValues($domain_node3);
    $this->assertTrue(isset($assigned[$two]), 'Node3 assigned to proper test domain.');
    $assigned = $this->manager->getAccessValues($domain_node4);
    $this->assertTrue(isset($assigned[$two]), 'Node4 assigned to proper test domain.');

    // Tests 'edit TYPE content on assigned domains.'
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $domain_node3, $domain_user3);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $domain_node4, $domain_user3);

    // @TODO: Test edit and delete for user with 'all affiliates' permission.
    // Tests 'edit domain TYPE content'.
    // Assign our user to domain $two. Test on $one and $two.
    $domain_user4 = $this->drupalCreateUser(array(
      'access content',
      'update page content on assigned domains',
      'delete page content on assigned domains',
    ));
    $this->addDomainToEntity('user', $domain_user4->id(), $two);
    $this->addDomainToEntity('user', $domain_user4->id(), 1, DOMAIN_ACCESS_ALL_FIELD);
    $domain_user4 = \Drupal::entityManager()->getStorage('user')->load($domain_user4->id());
    $assigned = $this->manager->getAccessValues($domain_user4);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');
    $this->assertTrue(!empty($domain_user4->get(DOMAIN_ACCESS_ALL_FIELD)->value), 'User assign to all affiliates.');

    // Assign two different node types to our test domain.
    $domain_node5 = $this->drupalCreateNode(array('type' => 'article', DOMAIN_ACCESS_FIELD => [$one]));
    $domain_node6 = $this->drupalCreateNode(array('type' => 'page', DOMAIN_ACCESS_FIELD => [$one]));
    $assigned = $this->manager->getAccessValues($domain_node5);
    $this->assertTrue(isset($assigned[$one]), 'Node5 assigned to proper test domain.');
    $assigned = $this->manager->getAccessValues($domain_node6);
    $this->assertTrue(isset($assigned[$one]), 'Node6 assigned to proper test domain.');

    // Tests 'edit TYPE content on assigned domains.'
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $domain_node5, $domain_user4);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $domain_node6, $domain_user4);

    // Tests create permissions. Any content on assigned domains.
    $domain_user5 = $this->drupalCreateUser(array('access content', 'create domain content'));
    $this->addDomainToEntity('user', $domain_user5->id(), $two);
    $domain_user5 = \Drupal::entityManager()->getStorage('user')->load($domain_user5->id());
    $assigned = $this->manager->getAccessValues($domain_user5);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');
    // This test is domain sensitive.
    foreach ($domains as $domain) {
      $this->domainLogin($domain, $domain_user5);
      $url = $domain->getPath() . 'node/add/page';
      $this->drupalGet($url);
      if ($domain->id() == $two) {
        $this->assertResponse(200);
      }
      else {
        $this->assertResponse(403);
      }
    }
    // Tests create permissions. Page content on assigned domains.
    $domain_user5 = $this->drupalCreateUser(array('access content', 'create page content on assigned domains'));
    $this->addDomainToEntity('user', $domain_user5->id(), $two);
    $domain_user5 = \Drupal::entityManager()->getStorage('user')->load($domain_user5->id());
    $assigned = $this->manager->getAccessValues($domain_user5);
    $this->assertTrue(count($assigned) == 1, 'User assigned to one domain.');
    $this->assertTrue(isset($assigned[$two]), 'User assigned to proper test domain.');
    // This test is domain sensitive.
    foreach ($domains as $domain) {
      $this->domainLogin($domain, $domain_user5);
      $url = $domain->getPath() . 'node/add/page';
      $this->drupalGet($url);
      if ($domain->id() == $two) {
        $this->assertResponse(200);
      }
      else {
        $this->assertResponse(403);
      }
      $url = $domain->getPath() . 'node/add/article';
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
  public function assertNodeAccess(array $ops, Node $node, AccountInterface $account) {
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
  public function nodeAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Node access returns @result with operation %op, language code %langcode.',
      array(
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      )
    );
  }

}
