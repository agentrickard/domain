<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainForms
 */

namespace Drupal\domain\Tests;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Tests the domain record interface.
 */
class DomainForms extends DomainTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Domain record interface',
      'description' => 'Tests the domain record user interface.',
      'group' => 'Domain',
    );
  }

  /**
   * Create, edit and delete a domain via the user interface.
   */
  function testDomainInterface() {
    $this->admin_user = $this->drupalCreateUser(array('administer domains'));
    $this->drupalLogin($this->admin_user);

    // Visit the main domain administration page.
    $this->drupalGet('admin/structure/domain');
    // Visit the add domain administration page.
    $this->drupalGet('admin/structure/domain/add');

    // Make a POST request on admin/structure/domain/add.
    $edit = $this->domainPostValues();
    $this->drupalPost('admin/structure/domain/add', $edit, t('Save'));

    // Did it save correctly?
    $default_id = domain_default_id();
    $this->assertTrue(!empty($default_id), t('Domain record saved via form.'));

    // Does it load correctly?
    $new_domain = domain_load($default_id);
    $this->assertTrue($new_domain->machine_name == $edit['machine_name'], 'Domain loaded properly.');

    // Visit the edit domain administration page.
    $postUrl = 'admin/structure/domain/' . $new_domain->machine_name;
    $this->drupalGet($postUrl);

    // Update the record.
    $edit['name'] = 'Foo';
    $this->drupalPost($postUrl, $edit, t('Save'));

    // Check that the update succeeded.
    $domain = domain_load($default_id, TRUE);
    $this->assertTrue($domain->name == 'Foo', 'Domain record updated via form.');

    // Delete the record.
    $this->drupalPost($postUrl, $edit, t('Delete'));

    // No domains should exist.
    $this->domainTableIsEmpty();
  }

}
