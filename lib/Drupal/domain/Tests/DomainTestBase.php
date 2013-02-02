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

}
