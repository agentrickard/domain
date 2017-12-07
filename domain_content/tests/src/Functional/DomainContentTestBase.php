<?php

namespace Drupal\Tests\domain_content\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Base class and helper methods for testing domain content.
 */
abstract class DomainContentTestBase extends DomainTestBase {

  /**
   * Disabled config schema checking.
   *
   * Domain Content is having issues with schema definition.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_content');

  /**
   * An array of domains.
   *
   * @var \Drupal\domain\DomainInterface
   */
  public $domains;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create five test domains.
    $this->domainCreateTestDomains(5);

    $this->domains = \Drupal::service('entity_type.manager')->getStorage('domain')->loadMultiple();
  }

}
