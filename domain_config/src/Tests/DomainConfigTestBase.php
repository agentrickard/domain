<?php

namespace Drupal\domain_config\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\domain\Tests\DomainTestBase;

/**
 * Helper test methods for Domain Config testing.
 */
abstract class DomainConfigTestBase extends DomainTestBase {

  /**
   * Disabled config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Languages to enable.
   *
   * Note that English is already enabled.
   *
   * @var array
   */
  protected $langcodes = array('es' => 'Spanish');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'language', 'domain_config_test', 'domain_config');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Add language.
    $edit = array(
      'predefined_langcode' => 'es',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->drupalLogout();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertTrue(!empty($es), 'Created test language.');
  }

  /**
   * Generates a list of domains for testing.
   *
   * To allow test to work in different environments, we provide custom
   * machine names so that the configuration can be correctly loaded.
   *
   * The rest should be the same as DomainTestBase:: domainCreateTestDomains().
   *
   * @param int $count
   *   The number of domains to create.
   * @param string|NULL $base_hostname
   *   The root domain to use for domain creation (e.g. example.com).
   * @param array $list
   *   An optional list of subdomains to apply instead of the default set.
   */
  public function domainCreateTestDomains($count = 1, $base_hostname = NULL, $list = array()) {
    $original_domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    if (empty($base_hostname)) {
      $base_hostname = $this->base_hostname;
    }

    if (empty($list)) {
      $list = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
    }
    for ($i = 0; $i < $count; $i++) {
      if (!empty($list[$i])) {
        if ($i < 11) {
          $machine_name = $list[$i] . '.example.com';
          $hostname = $list[$i] . '.' . $base_hostname;
          $name = ucfirst($list[$i]);
        }
        // These domains are not setup and are just for UX testing.
        else {
          $hostname = $machine_name = 'test' . $i . '.' . $base_hostname;
          $name = 'Test ' . $i;
        }
      }
      else {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      // Create a new domain programmatically.
      $values = array(
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::service('domain.creator')->createMachineName($machine_name),
      );
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->save();
    }
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue((count($domains) - count($original_domains)) == $count, new FormattableMarkup('Created %count new domains.', array('%count' => $count)));
  }

}
