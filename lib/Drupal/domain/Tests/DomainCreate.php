<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainCreate
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain record creation API.
 */
class DomainCreate extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain record creation',
      'description' => 'Tests domain record CRUD API.',
      'group' => 'Domain',
    );
  }

  /**
   * Test initial domain creation.
   */
  function testDomainCreate() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $domain = domain_create();
    foreach (array('domain_id', 'hostname', 'name', 'machine_name') as $key) {
      $this->assertTrue(is_null($domain->{$key}->value), format_string('New $domain->!key property is set to NULL.', array('!key' => $key)));
    }
    foreach (array('scheme', 'status', 'weight' , 'is_default') as $key) {
      $this->assertTrue(isset($domain->{$key}->value), format_string('New $domain->!key property is set to default value: %value.', array('!key' => $key, '%value' => $domain->{$key}->value)));
    }
    // Now add the additional fields and save.
    $domain->hostname = $this->base_hostname;
    $domain->machine_name = domain_machine_name($domain->hostname->value);
    $domain->name = 'Default';
    $domain->save();

    // Did it save correctly?
    $default_id = domain_default_id();
    $this->assertTrue(!empty($default_id), 'Default domain has been set.');

    // Does it load correctly?
    $new_domain = domain_load($default_id);
    $this->assertTrue($new_domain->machine_name->value == $domain->machine_name->value, 'Domain loaded properly.');

    // Has a UUID been set?
    $this->assertTrue($new_domain->uuid(), 'Entity UUID set properly.');

    // Delete the domain.
    $domain->delete();
    $domain = domain_load($default_id, TRUE);
    $this->assertTrue(empty($domain), 'Domain record deleted.');

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Try the create function with server inheritance.
    $domain = domain_create(TRUE);
    foreach (array('domain_id') as $key) {
      $this->assertTrue(is_null($domain->{$key}->value), format_string('New $domain->!key property is set to NULL.', array('!key' => $key)));
    }
    foreach (array('hostname', 'name', 'machine_name', 'scheme', 'status', 'weight' , 'is_default') as $key) {
      $this->assertTrue(isset($domain->{$key}->value), format_string('New $domain->!key property is set to a default value: %value.', array('!key' => $key, '%value' => $domain->{$key}->value)));
    }
  }

}
