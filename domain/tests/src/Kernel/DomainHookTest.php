<?php

namespace Drupal\Tests\domain\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests domain hooks documented in domain.api.php.
 *
 * Note that the other hooks are covered by functional tests, since they involve UI
 * elements.
 *
 * @see DomainReferencesTest
 * @see DomainListBuilderTes
 * @see DomainAliasNegotiatorTest
 *
 * @group domain
 */
class DomainHookTest extends DomainTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test');

  /**
   * Tests domain loading.
   */
  public function testHookDomainLoad() {

    // Create a domain.
    $this->domainCreateTestDomains();

    // Check the created domain based on it's known id value.
    $key = 'example_com';

    $domain = \Drupal::service('domain.loader')->load($key);

    // Internal hooks.
    $path = $domain->getPath();
    $url = $domain->getUrl();
    $this->assertTrue(isset($path), new FormattableMarkup('The path property was set to %path by hook_entity_load.', array('%path' => $path)));
    $this->assertTrue(isset($url), new FormattableMarkup('The url property was set to %url by hook_entity_load.', array('%url' => $url)));

    // External hooks.
    $this->assertTrue($domain->foo == 'bar', 'The foo property was set to <em>bar</em> by hook_domain_load.');
  }

  /**
   * Tests domain validation.
   */
  public function testHookDomainValidate() {
    $validator = \Drupal::service('domain.validator');
    // Test a good domain.
    $errors = $validator->validate('example.com');
    $this->assertEmpty($errors, 'No errors returned for example.com');

    // Test our hook implementation.
    $errors = $validator->validate('fail.example.com');
    $this->assertNotEmpty($errors, 'Errors returned for fail.example.com');
    $this->assertTrue(current($errors) == 'Fail.example.com cannot be registered', 'Error message returned correctly.');
  }

  /**
   * Tests domain request alteration.
   */
  public function testHookDomainRequestAlter() {

    // Create a domain.
    $this->domainCreateTestDomains();

    // Set the request.
    $negotiator = \Drupal::service('domain.negotiator');
    $negotiator->setRequestDomain($this->base_hostname);

    $domain = $negotiator->getActiveDomain();
    $this->assertTrue($domain->foo1 == 'bar1', 'The foo1 property was set to <em>bar1</em> by hook_domain_request_alter');

  }
}
