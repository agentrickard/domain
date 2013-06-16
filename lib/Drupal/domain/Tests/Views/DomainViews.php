<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\Views\DomainViews.
 */

namespace Drupal\domain\Tests\Views;
use Drupal\domain\Plugin\Core\Entity\Domain;
use Drupal\domain\Tests\DomainTestBase;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;


/**
 * Tests the domain record views integration.
 */
class DomainViews extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'action', 'domain_views_test', 'views', 'views_ui');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('domain_views_test');

  public function setUp() {
    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('domain_views_test'));
  }

  public static function getInfo() {
    return array(
      'name' => 'Domain views',
      'description' => 'Tests the domain Views user interface.',
      'group' => 'Domain',
    );
  }

  /**
   * Test bulk actions through the Views module.
   */
  function testDomainViewsActions() {
    $this->admin_user = $this->drupalCreateUser(array('administer domains', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    $path = 'admin/structure/domain_views_test';

    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create test domains.
    $this->domainCreateTestDomains(4);

    // Visit the domains views administration page.
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test the domains.
    $domains = domain_load_multiple(NULL, TRUE);
    $this->assertTrue(count($domains) == 4, 'Four domain records found.');

    // Check the default domain.
    $default = domain_default_id();
    $this->assertTrue($default == 1, 'Default domain set correctly.');

    // Test some text on the page.
    foreach ($domains as $domain) {
      $this->assertText($domain->name, format_string('@name found on views page.', array('@name' => $domain->name)));
      $this->assertText($domain->machine_name, format_string('@machine_name found on views page.', array('@machine_name' => $domain->machine_name)));
    }
    // @TODO: Test the list of actions.
    $actions = array('domain_delete_action', 'domain_enable_action', 'domain_disable_action', 'domain_default_action');
    foreach ($actions as $action) {
      $this->assertRaw("<option value=\"{$action}\">", format_string('@action action found.', array('@action' => $action)));
    }

    // Testing domain_delete_action.
    $edit = array(
      'action_bulk_form[1]' => TRUE,
      'action' => 'domain_delete_action',
    );

    $this->drupalPost($path, $edit, t('Apply'));
    $this->assertText('Delete domain record was applied to 1 item.');

    // Check that one domain was removed.
    $domains = domain_load_multiple(NULL, TRUE);
    $this->assertTrue(count($domains) == 3, 'One domain deleted.');

    // Testing domain_default_action.
    $edit = array(
      'action_bulk_form[1]' => TRUE,
      'action' => 'domain_default_action',
    );
    $this->drupalPost($path, $edit, t('Apply'));
    $this->assertText('Set default domain record was applied to 1 item.');

    // Test the default domain, which should now be id 3.
    $default = domain_default_id();
    $this->assertTrue($default == 3, 'Default domain set correctly.');


    // Testing domain_disable_action.
    $edit = array(
      'action_bulk_form[1]' => TRUE,
      'action_bulk_form[2]' => TRUE,
      'action' => 'domain_disable_action',
    );
    $this->drupalPost($path, $edit, t('Apply'));
    $this->assertText('The default domain cannot be disabled.');
    $this->assertText('Disable domain record was applied to 2 items.');

    // @TODO: Test the count of disabled domains.

    // Testing domain_enable_action.
    $edit = array(
      'action_bulk_form[2]' => TRUE,
      'action' => 'domain_enable_action',
    );
    $this->drupalPost($path, $edit, t('Apply'));
    $this->assertText('Enable domain record was applied to 1 item.');

    // @TODO: Test the count of disabled domains.


  }

}
