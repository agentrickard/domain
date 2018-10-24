<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record response check.
 *
 * @group domain
 */
class DomainCheckResponseTest extends DomainTestBase {

  /**
   * Tests that a domain responds as expected.
   */
  public function testDomainCheckResponse() {
    $this->admin_user = $this->drupalCreateUser(['administer domains', 'create domains']);
    $this->drupalLogin($this->admin_user);

    $storage = \Drupal::entityTypeManager()->getStorage('domain');

    // Make a POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    $this->drupalPostForm('admin/config/domain/add', $edit, 'Save');

    // Did it save correctly?
    $this->assertNoRaw('The server request to');
    $domains = $storage->loadMultiple();
    $this->assertTrue(count($domains) == 1, 'Domain record saved via form.');

    // Make an invalid POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    // Set a hostname that does not exist on the server.
    $edit['hostname'] = 'foo.bar';
    $edit['id'] = $storage->createMachineName($edit['hostname']);
    $edit['validate_url'] = 1;
    try {
      $this->drupalPostForm('admin/config/domain/add', $edit, 'Save');
    }
    catch (\Exception $e) {
      // Ensure no test errors.
    }
    // The domain should not save.
    $this->assertRaw('The server request to');
    $domains = $storage->loadMultiple();
    $this->assertTrue(count($domains) == 1, 'Domain record not saved via form.');

    // Bypass the check.
    $edit['validate_url'] = 0;
    $this->drupalPostForm('admin/config/domain/add', $edit, 'Save');

    // The domain should save.
    $this->assertNoRaw('The server request to');
    $domains = $storage->loadMultiple();
    $this->assertTrue(count($domains) == 2, 'Domain record saved via form.');
  }

}
