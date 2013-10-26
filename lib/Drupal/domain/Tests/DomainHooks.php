<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainHooks
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain record creation API.
 */
class DomainHooks extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test');


  public static function getInfo() {
    return array(
      'name' => 'Domain module hooks',
      'description' => 'Tests domain module API hooks.',
      'group' => 'Domain',
    );
  }

  /**
   * Test domain loading.
   */
  function testHookDomainLoad() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a domain.
    $this->domainCreateTestDomains();

    // @TODO: We need a new loader?
    $key = domain_machine_name(domain_hostname());

    $domain = domain_load($key);

    // Internal hooks.
    $this->assertTrue(isset($domain->path), format_string('The path property was set to %path by hook_entity_load.', array('%path' => $domain->path)));
    $this->assertTrue(isset($domain->url), format_string('The url property was set to %url by hook_entity_load.', array('%url' => $domain->url)));

    // External hooks.
    $this->assertTrue($domain->foo == 'bar', 'The foo property was set to <em>bar</em> by hook_domain_load.');
  }

}
