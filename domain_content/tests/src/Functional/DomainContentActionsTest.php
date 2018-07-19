<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Tests the assign / unassign actions on a Domain Content view.
 *
 * @group domain_content
 */
class DomainContentActionsTest extends DomainContentTestBase {

  /**
   * Tests domain content actions.
   */
  public function testDomainContentActions() {
    // This user should be able to see everything.
    $this->admin_user = $this->drupalCreateUser([
      'administer domains',
      'access administration pages',
      'access domain content',
      'access domain content editors',
      'publish to any domain',
      'assign editors to any domain',
      // Edit access is required. This is fastest.
      'bypass node access',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create users and content.
    $this->createDomainContent();

    $url = 'admin/content/domain-content/all_affiliates';

    $this->drupalGet($url);

    // All the content should be on domain one.
    $old_domain = $this->domains['one_example_com'];
    $new_domain = $this->domains['two_example_com'];

    // Domains are linked in the output.
    $this->assertRaw($old_domain->label() . '</a>');
    $this->assertNoRaw($new_domain->label() . '</a>');

    // Add some content to domain two.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'action' => 'domain_access_add_action.two_example_com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    // Both domains should be present.
    $this->assertRaw($old_domain->label() . '</a>');
    $this->assertRaw($new_domain->label() . '</a>');

    // Remove some content from domain two.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'action' => 'domain_access_remove_action.two_example_com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    // Domains are linked properly in the output.
    $this->assertRaw($old_domain->label() . '</a>');
    $this->assertNoRaw($new_domain->label() . '</a>');

    // There should be five elements.
    $this->assertRaw('node_bulk_form[4]');

    // Remove one from all affiliates.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'domain_access_none_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    // There should be four elements.
    $this->assertRaw('node_bulk_form[3]');
    $this->assertNoRaw('node_bulk_form[4]');
  }

}
