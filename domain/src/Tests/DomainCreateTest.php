<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainCreateTest.
 */

namespace Drupal\domain\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain\Tests\DomainTestBase;

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
    $domain = \Drupal::service('domain.creator')->createDomain();
    foreach (array('id', 'name', 'hostname', 'domain_id', 'scheme', 'status', 'weight' , 'is_default') as $key) {
      $property = $domain->get($key);
      $this->assertTrue(isset($property), format_string('New $domain->@key property is set to default value: %value.', array('@key' => $key, '%value' => $property)));
    }
    $domain->save();

    // Did it save correctly?
    $default_id = \Drupal::service('domain.loader')->loadDefaultId();
    $this->assertTrue(!empty($default_id), 'Default domain has been set.');

    // Does it load correctly?
    $new_domain = \Drupal::service('domain.loader')->load($default_id);
    $this->assertTrue($new_domain->id() == $domain->id(), 'Domain loaded properly.');

    // Has domain id been set?
    $this->assertTrue($new_domain->getDomainId() == 1, 'Domain id set properly.');

    // Has a UUID been set?
    $this->assertTrue($new_domain->uuid(), 'Entity UUID set properly.');

    // Delete the domain.
    $domain->delete();
    $domain = \Drupal::service('domain.loader')->load($default_id, TRUE);
    $this->assertTrue(empty($domain), 'Domain record deleted.');

    // No domains should exist.
    $this->domainTableIsEmpty();
  }

}
