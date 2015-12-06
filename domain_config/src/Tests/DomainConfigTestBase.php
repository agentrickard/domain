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
  public static $modules = array('language', 'domain_config_test');

  /**
   * Test setup.
   */
  function setUp() {
    parent::setUp();
    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // Let anon users see content.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access content'));
  }

}
