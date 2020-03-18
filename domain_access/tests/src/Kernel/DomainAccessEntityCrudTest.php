<?php

namespace Drupal\Tests\domain_access\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests creation of nodes and users before and after deleting required fields.
 *
 * @group domain_access
 */
class DomainAccessEntityCrudTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  use NodeCreationTrait {
    createNode as drupalCreateNode;
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'field', 'filter', 'text', 'user', 'node', 'domain', 'domain_access'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installSchema('node', ['node_access']);
    $this->installConfig($this::$modules);

    $type = $this->entityTypeManager->getStorage('node_type')->create(['type' => 'page', 'name' => 'page']);
    $type->save();

    module_load_install('domain_access');
    domain_access_install();
  }

  /**
   * Delete domain access fields.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Entity type bundle.
   */
  protected function deleteDomainAccessFields($entity_type, $bundle) {
    $fields = [DOMAIN_ACCESS_FIELD, DOMAIN_ACCESS_ALL_FIELD];
    foreach ($fields as $field_name) {
      FieldConfig::loadByName($entity_type, $bundle, $field_name)->delete();
    }
  }

  /**
   * Tests node creation with installed domain access fields.
   */
  public function testNodeCreateWithInstalledDomainAccessFields() {
    $node = $this->drupalCreateNode();
    $node->save();
    self::assertNotEmpty($node->id());
  }

  /**
   * Tests node creation with uninstalled domain access fields.
   */
  public function testNodeCreateWithUninstalledDomainAccessFields() {
    $this->deleteDomainAccessFields('node', 'page');

    $node = $this->drupalCreateNode();
    $node->save();
    self::assertNotEmpty($node->id());
  }

  /**
   * Tests node update with installed domain access fields.
   */
  public function testNodeUpdateWithInstalledDomainAccessFields() {
    $node = $this->drupalCreateNode();
    $node->save();
    self::assertNotEmpty($node->id());

    $new_title = $this->randomMachineName(8);
    $node->setTitle($new_title);
    $node->save();
    self::assertSame($new_title, $node->getTitle());
  }

  /**
   * Tests node update with uninstalled domain access fields.
   */
  public function testNodeUpdateWithUninstalledDomainAccessFields() {
    $node = $this->drupalCreateNode();
    $node->save();
    self::assertNotEmpty($node->id());

    $this->deleteDomainAccessFields('node', 'page');
    $reloaded_node = $this->entityTypeManager->getStorage('node')->load($node->id());

    $new_title = $this->randomMachineName(8);
    $reloaded_node->setTitle($new_title);
    $reloaded_node->save();
    self::assertSame($new_title, $reloaded_node->getTitle());
  }

  /**
   * Tests user creation with installed domain access fields.
   */
  public function testUserCreateWithInstalledDomainAccessFields() {
    $user = $this->drupalCreateUser();
    $user->save();
    self::assertNotEmpty($user->id());
  }

  /**
   * Tests user creation with uninstalled domain access fields.
   */
  public function testUserCreateWithUninstalledDomainAccessFields() {
    $this->deleteDomainAccessFields('user', 'user');

    $user = $this->drupalCreateUser();
    $user->save();
    self::assertNotEmpty($user->id());
  }

  /**
   * Tests user update with installed domain access fields.
   */
  public function testUserUpdateWithInstalledDomainAccessFields() {
    $node = $this->drupalCreateNode();
    $node->save();
    self::assertNotEmpty($node->id());

    $new_title = $this->randomMachineName(8);
    $node->setTitle($new_title);
    $node->save();
    self::assertSame($new_title, $node->getTitle());
  }

  /**
   * Tests user update with uninstalled domain access fields.
   */
  public function testUserUpdateWithUninstalledDomainAccessFields() {
    $user = $this->drupalCreateUser();
    $user->save();
    self::assertNotEmpty($user->id());

    $this->deleteDomainAccessFields('user', 'user');
    $reloaded_user = $this->entityTypeManager->getStorage('user')->load($user->id());

    $new_name = $this->randomMachineName();
    $reloaded_user->setUsername($new_name);
    $reloaded_user->save();
    self::assertSame($new_name, $reloaded_user->getAccountName());
  }

}
