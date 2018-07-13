<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Unish\CommandUnishTestCase;

/**
 *  @group slow
 *  @group commands
 */
class DomainCommandTestBase extends CommandUnishTestCase {

  //  use TestModuleHelperTrait;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node');

  /**
   * We use the standard profile for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if (UNISH_DRUPAL_MAJOR_VERSION < 8) {
      $this->markTestSkipped('Migrate manifest is for D8');
    }
    // Install the standard install profile.
    $sites = $this->setUpDrupal(1, TRUE, UNISH_DRUPAL_MAJOR_VERSION, 'standard');
    $site = key($sites);
    $root = $this->webroot();
    $this->siteOptions = array(
      'root' => $root,
      'uri' => $site,
      'yes' => NULL,
    );
    $this->drush('pm-enable', ['domain', 'domain_access'], $this->siteOptions);
  }

}
