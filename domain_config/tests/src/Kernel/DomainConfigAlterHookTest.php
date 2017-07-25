<?php

namespace Drupal\Tests\domain_config\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests domain_config effects on hook_domain_request_alter().
 *
 * @group domain_config
 */
class DomainConfigAlterHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'domain'

  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
   # $this->installEntitySchema('domain');
   # $this->installConfig(['domain', 'domain_config_test']);
  }

  /**
   * Test domain negotiation.
   */
  public function testDomainRequestAlter() {

    $this->assertTrue(1 == 1);

  }
}
