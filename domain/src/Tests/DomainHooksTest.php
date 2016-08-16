<?php

namespace Drupal\domain\Tests;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\domain\DomainListBuilder;

/**
 * Tests the domain module hook invocations.
 *
 * @group domain
 */
class DomainHooksTest extends DomainTestBase {

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
    // No domains should exist.
    $this->domainTableIsEmpty();

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

    // @TODO: test additional hooks.
  }

  /**
   * Tests domain request alter.
   */
  public function testHookDomainRequestAlter() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a domain.
    $this->domainCreateTestDomains();

    \Drupal::service('domain.negotiator')->setRequestDomain($this->base_hostname, TRUE);
    
    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();

    // External hooks.
    $this->assertTrue($domain->foo1 == 'bar1', 'The foo1 property was set to <em>bar1</em> by hook_domain_request_alter.');
  }
  /**
   * Tests domain validate alter
   */
  public function testHookDomainValidateAlter() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a domain.
    $this->domainCreateTestDomains();

    // Check the created domain based on it's known id value.
    $key = 'example_com';

    $domain = \Drupal::service('domain.loader')->load($key);

    $validationResult = \Drupal::service('domain.validator')->validate($domain);

    $this->assertTrue(!empty($validationResult), 'The error was generated by hook_domain_validate_alter');
  }
}
