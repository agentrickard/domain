<?php

namespace Drupal\Tests\domain_config\Kernel;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests for https://www.drupal.org/node/2896434#comment-12265088.
 *
 * @group domain_config
 */
class DomainConfigAlterHookTest extends DomainTestBase {

  /**
   * Disable config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_config', 'domain_config_test');

  /**
   * Domain id key.
   */
  public $key = 'example_com';

  /**
   * The domain negotiator service.
   */
  public $negotiator;

  /**
   * The mondule handler service.
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
    $this->assertTrue(count($hooks) == 1, 'One hook implementation found.');

    // Set the request.
    $this->negotiator->setRequestDomain($this->base_hostname);

    // Check that the property was added by our hook.
    $domain = $this->negotiator->getActiveDomain();
    $this->assertTrue($domain->config_test == 'aye', 'The config_test property was set to <em>aye</em> by hook_domain_request_alter');
  }

}
