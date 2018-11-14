<?php

namespace Drupal\Tests\domain\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the ability to set a variable scheme on a domain.
 *
 * @group domain
 */
class DomainVariableSchemeTest extends KernelTestBase {

  use DomainTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain'];

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
   * Test setup.
   */
  protected function setUp() {
    parent::setUp();

    // Create a domain.
    $this->domainCreateTestDomains();

    // Get the services.
    $this->domainStorage = \Drupal::entityTypeManager()->getStorage('domain');
  }

  /**
   * Tests domain loading.
   */
  public function testDomainScheme() {
    // Set our testing parameters.
    $default_scheme = \Drupal::request()->getScheme();
    $alt_scheme = ($default_scheme == 'https') ? 'http' : 'https';
    $add_suffix = FALSE;

    // Our created domain should have a scheme that matches the request.
    $domain = $this->domainStorage->load($this->key);
    $this->assertTrue($domain->getScheme($add_suffix) == $default_scheme);

    // Swtich the scheme and see if that works.
    $domain->set('scheme', $alt_scheme);
    $domain->save();
    $domain = $this->domainStorage->load($this->key);
    $this->assertTrue($domain->getScheme($add_suffix) == $alt_scheme);

    // Set the scheme to variable, and that should match the default.
    $domain->set('scheme', 'variable');
    $domain->save();
    $this->assertTrue($domain->getScheme($add_suffix) == $default_scheme);
  }

}
