<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record form interface.
 *
 * @group domain
 */
class DomainFormsTest extends DomainTestBase {

  /**
   * Create, edit and delete a domain via the user interface.
   */
  public function testDomainInterface() {
    $this->admin_user = $this->drupalCreateUser(['administer domains', 'create domains']);
    $this->drupalLogin($this->admin_user);

    $storage = \Drupal::entityTypeManager()->getStorage('domain');

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Visit the main domain administration page.
    $this->drupalGet('admin/config/domain');

    // Check for the add message.
    if (substr_count(\Drupal::VERSION, '8.5') > 0) {
      $this->assertText('There is no Domain record yet.', 'Text for no domains found.');
    }
    else {
      $this->assertText('There are no domain record entities yet.', 'Text for no domains found.');
    }
    // Visit the add domain administration page.
    $this->drupalGet('admin/config/domain/add');

    // Make a POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    $this->drupalPostForm('admin/config/domain/add', $edit, 'Save');

    // Did it save correctly?
    $default_id = $storage->loadDefaultId();
    $this->assertTrue(!empty($default_id), 'Domain record saved via form.');

    // Does it load correctly?
    $storage->resetCache([$default_id]);
    $new_domain = $storage->load($default_id);
    $this->assertTrue($new_domain->id() == $default_id, 'Domain loaded properly.');

    // Has a UUID been set?
    $this->assertTrue(!empty($new_domain->uuid()), 'Entity UUID set properly.');

    // Visit the edit domain administration page.
    $editUrl = 'admin/config/domain/edit/' . $new_domain->id();
    $this->drupalGet($editUrl);

    // Update the record.
    $edit = [];
    $edit['name'] = 'Foo';
    $edit['validate_url'] = 0;
    $this->drupalPostForm($editUrl, $edit, 'Save');

    // Check that the update succeeded.
    $storage->resetCache([$default_id]);
    $domain = $storage->load($default_id);
    $this->assertTrue($domain->label() == 'Foo', 'Domain record updated via form.');

    // Visit the delete domain administration page.
    $deleteUrl = 'admin/config/domain/delete/' . $new_domain->id();
    $this->drupalGet($deleteUrl);

    // Delete the record.
    $this->drupalPostForm($deleteUrl, [], 'Delete');
    $storage->resetCache([$default_id]);
    $domain = $storage->load($default_id);
    $this->assertTrue(empty($domain), 'Domain record deleted.');

    // No domains should exist.
    $this->domainTableIsEmpty();
  }

}
