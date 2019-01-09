<?php

namespace Drupal\Tests\domain_config\Functional;

/**
 * Tests the domain config system.
 *
 * @group domain_config
 */
class DomainConfigHookProblemTest extends DomainConfigHookTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Adds the domain_config module.
    'domain_config',
  ];

  /**
   * Disabled config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

}
