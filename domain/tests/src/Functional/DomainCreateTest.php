<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record creation API.
 *
 * @group domain
 */
class DomainCreateTest extends DomainTestBase {

  /**
   * Tests initial domain creation.
   */
  public function testDomainCreate() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    $domain = $storage->create();
    $domain->set('id', $storage->createMachineName($domain->getHostname()));
    $keys = [
      'id',
      'name',
      'hostname',
      'scheme',
      'status',
      'weight',
      'is_default',
    ];
    foreach ($keys as $key) {
      $property = $domain->get($key);
      $this->assertTrue(isset($property), 'Property loaded');
    }
    $domain->save();

    // Did it save correctly?
    $default_id = $storage->loadDefaultId();
    $this->assertTrue(!empty($default_id), 'Default domain has been set.');

    // Does it load correctly?
    $new_domain = $storage->load($default_id);
    $this->assertTrue($new_domain->id() == $domain->id(), 'Domain loaded properly.');

    // Has domain id been set?
    $this->assertTrue($new_domain->getDomainId(), 'Domain id set properly.');

    // Has a UUID been set?
    $this->assertTrue($new_domain->uuid(), 'Entity UUID set properly.');

    // Delete the domain.
    $domain->delete();
    $domain = $storage->load($default_id, TRUE);
    $this->assertTrue(empty($domain), 'Domain record deleted.');

    // No domains should exist.
    $this->domainTableIsEmpty();
  }

}
