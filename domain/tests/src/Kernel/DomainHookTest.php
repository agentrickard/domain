<?php

namespace Drupal\Tests\domain\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests domain hooks documented in domain.api.php.
 *
 * Note that the other hooks are covered by functional tests, since they involve
 * UI elements.
 *
 * @see DomainReferencesTest
 * @see DomainListBuilderTes
 * @see DomainAliasNegotiatorTest
 *
 * @group domain
 */
class DomainHookTest extends KernelTestBase {

  use DomainTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_test', 'user', 'node'];

  /**
   * Domain id key.
   *
   * @var string
   */
  public $key = 'example_com';

  /**
   * The Domain storage handler service.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  public $domainStorage;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Test setup.
   */
  protected function setUp() {
    parent::setUp();

    // Create a domain.
    $this->domainCreateTestDomains();

    // Get the services.
    $this->domainStorage = \Drupal::entityTypeManager()->getStorage('domain');
    $this->currentUser = \Drupal::service('current_user');
    $this->moduleHandler = \Drupal::service('module_handler');
  }

  /**
   * Tests domain loading.
   */
  public function testHookDomainLoad() {
    // Check the created domain based on its known id value.
    $domain = $this->domainStorage->load($this->key);

    // Internal hooks.
    $path = $domain->getPath();
    $url = $domain->getUrl();
    $this->assertTrue(isset($path), new FormattableMarkup('The path property was set to %path by hook_entity_load.', ['%path' => $path]));
    $this->assertTrue(isset($url), new FormattableMarkup('The url property was set to %url by hook_entity_load.', ['%url' => $url]));

    // External hooks.
    $this->assertTrue($domain->foo == 'bar', 'The foo property was set to <em>bar</em> by hook_domain_load.');
  }

  /**
   * Tests domain validation.
   */
  public function testHookDomainValidate() {
    $validator = \Drupal::service('domain.validator');
    // Test a good domain.
    $errors = $validator->validate('one.example.com');
    $this->assertEmpty($errors, 'No errors returned for example.com');

    // Test our hook implementation, which denies fail.example.com explicitly.
    $errors = $validator->validate('fail.example.com');
    $this->assertNotEmpty($errors, 'Errors returned for fail.example.com');
    $this->assertTrue(current($errors) == 'Fail.example.com cannot be registered', 'Error message returned correctly.');
  }

  /**
   * Tests domain request alteration.
   */
  public function testHookDomainRequestAlter() {
    // Set the request.
    $negotiator = \Drupal::service('domain.negotiator');
    $negotiator->setRequestDomain($this->baseHostname);

    // Check that the property was added by our hook.
    $domain = $negotiator->getActiveDomain();
    $this->assertTrue($domain->foo1 == 'bar1', 'The foo1 property was set to <em>bar1</em> by hook_domain_request_alter');
  }

  /**
   * Tests domain operations hook.
   */
  public function testHookDomainOperations() {
    $domain = $this->domainStorage->load($this->key);

    // Set the request.
    $operations = $this->moduleHandler->invokeAll('domain_operations', [$domain, $this->currentUser]);

    // Test that our operations were added by the hook.
    $this->assertTrue(isset($operations['domain_test']), 'Domain test operation loaded.');
  }

  /**
   * Tests domain references alter hook.
   */
  public function testHookDomainReferencesAlter() {
    $domain = $this->domainStorage->load($this->key);

    // Set the request.
    $manager = \Drupal::service('entity.manager');
    $target_type = 'domain';

    // Build a node entity selection query.
    $query = $manager->getStorage($target_type)->getQuery();
    $context = [
      'entity_type' => 'node',
      'bundle' => 'article',
      'field_type' => 'editor',
    ];

    // Run the alteration, which should add metadata to the query for nodes.
    $this->moduleHandler->alter('domain_references', $query, $this->currentUser, $context);
    $this->assertTrue($query->getMetaData('domain_test') == 'Test string', 'Domain test query altered.');

    // Build a user entity selection query.
    $query = $manager->getStorage($target_type)->getQuery();
    $context = [
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_type' => 'admin',
    ];

    // Run the alteration, which does not add metadata for user queries.
    $this->moduleHandler->alter('domain_references', $query, $this->currentUser, $context);
    $this->assertEmpty($query->getMetaData('domain_test'), 'Domain test query not altered.');
  }

}
