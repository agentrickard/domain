<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests access to domain entities.
 *
 * @link https://www.drupal.org/project/domain/issues/3128421
 *
 * @group domain
 */
class DomainEntityAccessTest extends DomainTestBase {

  /**
   * Tests initial domain creation.
   */
  public function testDomainCreate() {
    $admin = $this->drupalCreateUser([
      'access administration pages',
      'administer domains',
    ]);
    $this->drupalLogin($admin);
    $storage = \Drupal::entityTypeManager()->getStorage('domain');

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Visit the main domain administration page.
    $this->drupalGet('admin/config/domain');

    // Check for the add message.
    $this->assertText('There are no domain record entities yet.', 'Text for no domains found.');

    // Visit the add domain administration page.
    $this->drupalGet('admin/config/domain/add');

    // Make a POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    // Use hostname with dot (.) to avoid validation error.
    $edit['hostname'] = 'example.com';
    $this->drupalGet('admin/config/domain/add');
    $this->submitForm($edit, 'Save');

    // Did it save correctly?
    $default_id = $storage->loadDefaultId();
    $this->assertNotEmpty($default_id, 'Domain record saved via form.');

    // Does it load correctly?
    $storage->resetCache([$default_id]);
    $new_domain = $storage->load($default_id);
    $this->assertTrue($new_domain->id() == $default_id, 'Domain loaded properly.');

    $this->drupalLogout();
    $editor = $this->drupalCreateUser([
      'access administration pages',
      'create domains',
      'view domain list',
    ]);
    $this->drupalLogin($editor);

    // Visit the add domain add page.
    $this->drupalGet('admin/config/domain/add');
    $this->assertResponse(200);
    // Make a POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    // Use hostname with dot (.) to avoid validation error.
    $edit['hostname'] = 'one.example.com';
    $edit['id'] = \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($edit['hostname']);
    $this->drupalGet('admin/config/domain/add');
    $this->submitForm($edit, 'Save');

    // Does it load correctly?
    $storage->resetCache([$edit['id']]);
    $new_domain = $storage->load($edit['id']);
    $this->assertTrue($new_domain->id() == $edit['id'], 'Domain loaded properly.');

    $this->drupalLogout();
    $noneditor = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->drupalLogin($noneditor);
    // Visit the add domain administration page.
    $this->drupalGet('admin/config/domain/add');
    $this->assertResponse(403);
  }

}
