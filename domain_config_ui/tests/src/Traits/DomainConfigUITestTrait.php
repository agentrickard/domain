<?php

namespace Drupal\Tests\domain_config_ui\Traits;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait DomainConfigUITestTrait {

  /**
   * A user with full permissions to use the module.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with access administration but not this module.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $editorUser;

  /**
   * A user with access to domains but not language.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $limitedUser;

  /**
   * A user with permission to domains and language.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $languageUser;

  /**
   * Create an admin user.
   */
  public function createAdminUser() {
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer domains',
      'administer domain config ui',
      'administer site configuration',
      'administer languages',
      'administer themes',
      'set default domain configuration',
      'translate domain configuration',
      'use domain config ui',
      'view domain information',
    ]);
  }

  /**
   * Create an editor user.
   */
  public function createEditorUser() {
    $this->editorUser = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer site configuration',
      'administer languages',
    ]);
  }

  /**
   * Create a limited admin user.
   */
  public function createLimitedUser() {
    $this->limitedUser = $this->drupalCreateUser([
      'access administration pages',
      'administer languages',
      'administer site configuration',
      'use domain config ui',
      'set default domain configuration',
    ]);
  }

  /**
   * Create a language administrator.
   */
  public function createLanguageUser() {
    $this->languageUser = $this->drupalCreateUser([
      'access administration pages',
      'use domain config ui',
      'translate domain configuration',
      'administer site configuration',
    ]);
  }

}
