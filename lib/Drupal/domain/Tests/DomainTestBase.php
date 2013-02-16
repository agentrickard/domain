<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainCreate
 */

namespace Drupal\domain\Tests;
use Drupal\simpletest\WebTestBase;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record interface.
 */
abstract class DomainTestBase extends WebTestBase {

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
    $domain = (array) domain_create(TRUE);
    $required = domain_required_fields();
    foreach ($domain as $key => $value) {
      if (in_array($key, $required)) {
        $edit[$key] = $value;
      }
    }
    return $edit;
  }

  public function domainCreateTestDomains($count = 1, $basename = NULL) {
    $original_domains = domain_load_multiple(NULL, TRUE);
    if (empty($basename)) {
      $basename = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    }
    $list = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
    for ($i = 0; $i < $count; $i++) {
      // Note: these domains are rigged to work on my test server.
      if (!empty($list[$i])) {
        if ($i < 11) {
          $hostname = $list[$i] . '.' . $basename;
          $name = ucfirst($list[$i]);
        }
        // These domains are not.
        else {
          $hostname = 'test' . $i . '.' . $basename;
          $name = 'Test ' . $i;
        }
      }
      else {
        $hostname = $basename;
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
