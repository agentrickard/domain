<?php

namespace Drupal\Tests\domain_config\Functional;

/**
 * Tests for https://www.drupal.org/node/2896434#comment-12265088.
 *
 * @group domain_config
 */
class DomainConfigAlterHookTest extends DomainConfigTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'domain_config',
    'domain_config_test',
    'domain_config_middleware_test',
  ];

  /**
   * Domain id key.
   *
   * @var string
   */
  public $key = 'example_com';

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  public $negotiator;

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
    $this->negotiator = \Drupal::service('domain.negotiator');
    $this->moduleHandler = \Drupal::service('module_handler');
  }

  /**
   * Tests domain request alteration.
   */
  public function testHookDomainRequestAlter() {
    // Check for the count of hook implementations.
    $hooks = $this->moduleHandler->getImplementations('domain_request_alter');
    $this->assertCount(1, $hooks, 'One hook implementation found.');

    // Assert that the hook is also called on a request with a HTTP Middleware
    // that requests config thus triggering an early hook invocation (before
    // modules are loaded by the kernel).
    $this->drupalGet('<front>');
    $this->assertEquals('invoked', $this->drupalGetHeader('X-Domain-Config-Test-page-attachments-hook'));
  }

}
