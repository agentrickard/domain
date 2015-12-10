<?php

/**
 * @file
 * Definition of Drupal\domain_config\Tests\DomainConfigTestBase.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain\Tests\DomainTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Helper test methods for Domain Config testing.
 */
abstract class DomainConfigTestBase extends DomainTestBase {

  /**
   * Disabled config schema checking because Domain Config actually duplicates
   * schemas provided by other modules, so cannot define its own.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Languages to enable.
   *
   * Note that English is already enabled.
   *
   * @var array
   */
  protected $langcodes = array('es');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'language', 'domain_config_test', 'domain_config');

  /**
   * Test setup.
   */
  function setUp() {
    parent::setUp();
    // Add languages. If we use the createFromLangcode() method, it causes a
    // circular dependency.
    foreach ($this->langcodes as $langcode) {
      $values = [
        'id' => $langcode,
        'label' => $langcode,
      ];
      \Drupal::entityManager()->getStorage('configurable_language')->create($values);
    }
    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();
    $es = ConfigurableLanguage::load('es');
    $this->assertTrue(!empty($es));
    // Let anon users see content.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access content'));
  }

}
