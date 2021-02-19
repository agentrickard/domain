<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain negotiation manager with middleware.
 *
 * @see https://www.drupal.org/project/domain/issues/3195219
 *
 * @group domain
 */
class DomainMiddlewareTest extends DomainNegotiatorTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'domain',
    'domain_config',
    'domain_config_test',
    'domain_config_middleware_test',
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
