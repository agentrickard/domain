<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainCreateTest.
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain record creation API.
 *
 * @group domain
 */
class DomainCreateTest extends DomainTestBase {

  /**
   * Tests initial domain creation.
   */
  function testDomainCreate() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    // @TODO: This may need a refactor.
    $domain = domain_create();
    foreach (array('id', 'name', 'hostname') as $key) {
      $this->assertTrue(is_null($domain->get($key)), format_string('New $domain->!key property is set to NULL.', array('!key' => $key)));
    }
    foreach (array('domain_id', 'scheme', 'status', 'weight' , 'is_default') as $key) {
      $property = $domain->get($key);
      $this->assertTrue(isset($property), format_string('New $domain->!key property is set to default value: %value.', array('!key' => $key, '%value' => $property)));
    }
    // Now add the additional fields and save.
    $domain->addProperty('hostname', $this->base_hostname);
    $domain->addProperty('id', domain_machine_name($this->base_hostname));
    $domain->addProperty('name', 'Default');
    $domain->save();

    // Did it save correctly?
    $default_id = domain_default_id();
    $this->assertTrue(!empty($default_id), 'Default domain has been set.');

    // Does it load correctly?
    $new_domain = domain_load($default_id);
    $this->assertTrue($new_domain->id() == $domain->id(), 'Domain loaded properly.');

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
    // @TODO: This may need a refactor.
    foreach (array('domain_id', 'hostname', 'name', 'id', 'scheme', 'status', 'weight' , 'is_default') as $key) {
      $property = $domain->get($key);
      $this->assertTrue(isset($property), format_string('New $domain->!key property is set to a default value: %value.', array('!key' => $key, '%value' => $property)));
    }
  }

}
