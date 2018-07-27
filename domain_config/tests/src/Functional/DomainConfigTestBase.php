<?php

namespace Drupal\Tests\domain_config\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Helper test methods for Domain Config testing.
 */
abstract class DomainConfigTestBase extends DomainTestBase {

  /**
   * Disabled config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Languages to enable.
   *
   * Note that English is already enabled.
   *
   * @var array
   */
  protected $langcodes = ['es' => 'Spanish'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain',
    'language',
    'domain_config_test',
    'domain_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($admin_user);

    // Add language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->drupalLogout();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $es = \Drupal::entityTypeManager()->getStorage('configurable_language')->load('es');
    $this->assertTrue(!empty($es), 'Created test language.');
  }

}
