<?php

/**
 * @file
 * Definition of Drupal\domain_config\Tests\DomainConfigTestBase.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain\Tests\DomainTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Language\LanguageInterface;

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
  protected $langcodes = array('es' => 'Spanish');

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

    // Add languages.
    foreach ($this->langcodes as $langcode => $label) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();
    $es = \Drupal::entityManager()->getStorage('configurable_language')->load('es');
    $this->assertTrue(!empty($es), 'Created test language.');
    // Let anon users see content.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access content'));
  }

}
