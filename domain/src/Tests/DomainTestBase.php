<?php

namespace Drupal\domain\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\domain\DomainInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Crypt;
use Drupal\user\UserInterface;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Base class with helper methods and setup for domain tests.
 *
 * @deprecated
 *  This class will be removed before the 8.1.0 release.
 *  Use DomainStorage instead, loaded through the EntityTypeManager.
 */
abstract class DomainTestBase extends WebTestBase {

  use DomainTestTrait;
  use StringTranslationTrait;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->baseHostname or the
   * domainCreateTestDomains() method.
   *
   * @var string
   */
  public $baseHostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Set the base hostname for domains.
    $this->setBaseHostname();
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account object to check.
   *
   * @return bool
   *   TRUE if a given user account is logged in, or FALSE.
   */
  protected function drupalUserIsLoggedIn(UserInterface $account) {
    // @TODO: This is a temporary hack for the test login fails when setting $cookie_domain.
    if (!isset($account->session_id)) {
      return (bool) $account->id();
    }
    // The session ID is hashed before being stored in the database.
    // @see \Drupal\Core\Session\SessionHandler::read()
    return (bool) db_query("SELECT sid FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = :sid", [':sid' => Crypt::hashBase64($account->session_id)])->fetchField();
  }

  /**
   * Login a user on a specific domain.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to log the user into.
   * @param \Drupal\user\UserInterface $account
   *   The user account to login.
   */
  public function domainLogin(DomainInterface $domain, UserInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Login.
    $url = $domain->getPath() . 'user/login';
    $edit = ['name' => $account->getAccountName(), 'pass' => $account->passRaw];
    $this->drupalPostForm($url, $edit, t('Log in'));

    // @see WebTestBase::drupalUserIsLoggedIn()
    if (isset($this->sessionId)) {
      $account->session_id = $this->sessionId;
    }
    $pass = $this->assert($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getUsername()]), 'User login');
    if ($pass) {
      $this->loggedInUser = $account;
      $this->container->get('current_user')->setAccount($account);
    }
  }

}
