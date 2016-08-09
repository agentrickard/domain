<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain record form interface.
 *
 * @group domain
 */
class DomainFormsTest extends DomainBrowserTestBase {

  /**
   * Create, edit and delete a domain via the user interface.
   */
  public function testDomainInterface() {
    $this->admin_user = $this->drupalCreateUser(array(
      'administer domains',
      'create domains'
    ));

    $this->drupalLogin($this->admin_user);

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Visit the main domain administration page.
    $this->drupalGet('admin/config/domain');

    // Check for the add message.
    $this->assertSession()->pageTextContains('There is no Domain record yet.');

    // Visit the add domain administration page.
    $this->drupalGet('admin/config/domain/add');

    // Make a POST request on admin/config/domain/add.
    $edit = $this->domainPostValues();
    $this->drupalPostForm('admin/config/domain/add', $edit, 'Save');

    // Did it save correctly?
    $default_id = \Drupal::service('domain.loader')->loadDefaultId();
    $this->assertTrue(!empty($default_id), 'Domain record saved via form.');

    // Does it load correctly?
    $new_domain = \Drupal::service('domain.loader')->load($default_id);
    $this->assertTrue($new_domain->id() == $edit['id'], 'Domain loaded properly.');

    // Has a UUID been set?
    $uuid = $new_domain->uuid();
    $this->assertTrue(!empty($uuid), 'Entity UUID set properly.');

    // Visit the edit domain administration page.
    $editUrl = 'admin/config/domain/edit/' . $new_domain->id();
    $this->drupalGet($editUrl);

    // Update the record.
    $edit['name'] = 'Foo';
    $this->drupalPostForm($editUrl, $edit, $this->t('Save'));

    // Check that the update succeeded.
    $domain = \Drupal::service('domain.loader')->load($default_id, TRUE);
    $this->assertTrue($domain->label() == 'Foo', 'Domain record updated via form.');

    // Visit the delete domain administration page.
    $deleteUrl = 'admin/config/domain/delete/' . $new_domain->id();
    $this->drupalGet($deleteUrl);

    // Delete the record.
    $this->drupalPostForm($deleteUrl, array(), $this->t('Delete'));
    $domain = \Drupal::service('domain.loader')->load($default_id, TRUE);
    $this->assertTrue(empty($domain), 'Domain record deleted.');

    // No domains should exist.
    $this->domainTableIsEmpty();
  }
}
