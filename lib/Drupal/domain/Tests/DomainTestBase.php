<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainTestBase
 */

namespace Drupal\domain\Tests;
use Drupal\simpletest\WebTestBase;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain record interface.
 */
abstract class DomainTestBase extends WebTestBase {

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
  public static $modules = array('domain');

  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // Set the base hostname for domains.
    $this->base_hostname = domain_hostname();
  }

  /**
   * Reusable test function for checking initial / empty table status.
   */
  public function domainTableIsEmpty() {
    $domains = domain_load_multiple(NULL, TRUE);
    $this->assertTrue(empty($domains), 'No domains have been created.');
    $default_id = domain_default_id();
    $this->assertTrue(empty($default_id), 'No default domain has been set.');
  }

  /**
   * Creates domain record for use with POST request tests.
   */
  public function domainPostValues() {
    $edit = array();
    $domain = domain_create(TRUE);
    $required = domain_required_fields();
    foreach ($required as $key) {
      $edit[$key] = $domain->{$key}->value;
    }
    return $edit;
  }

  public function domainCreateTestDomains($count = 1, $base_hostname = NULL, $list = array()) {
    $original_domains = domain_load_multiple(NULL, TRUE);
    if (empty($base_hostname)) {
      $base_hostname = $this->base_hostname;
    }
    // Note: these domains are rigged to work on my test server.
    // For proper testing, yours should be set up similarly, but you can pass a
    // $list array to change the default.
    if (empty($list)) {
      $list = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
    }
    for ($i = 0; $i < $count; $i++) {
      if (!empty($list[$i])) {
        if ($i < 11) {
          $hostname = $list[$i] . '.' . $base_hostname;
          $name = ucfirst($list[$i]);
        }
        // These domains are not setup and are just for UX testing.
        else {
          $hostname = 'test' . $i . '.' . $base_hostname;
          $name = 'Test ' . $i;
        }
      }
      else {
        $hostname = $base_hostname;
        $name = 'Example';
      }
      // Create a new domain programmatically.
      $domain = domain_create();
      // Now add the additional fields and save.
      $domain->hostname = $hostname;
      $domain->machine_name = domain_machine_name($domain->hostname);
      $domain->name = $name;
      $domain->save();
    }
    $domains = domain_load_multiple(NULL, TRUE);
    $this->assertTrue((count($domains) - count($original_domains)) == $count, format_string('Created %count new domains.', array('%count' => $count)));
  }

}
