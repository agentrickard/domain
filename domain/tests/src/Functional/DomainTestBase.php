<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Tests\BrowserTestBase;
use Drupal\domain\DomainInterface;
use Drupal\Tests\domain\Traits\DomainTestTrait;

abstract class DomainTestBase extends BrowserTestBase {

  use DomainTestTrait;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node');

  /**
   * We use the standard profile for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the base hostname for domains.
    $this->base_hostname = \Drupal::entityTypeManager()->getStorage('domain')->createHostname();
  }

  /**
   * The methods below are brazenly copied from Rules module. They are all
   * helper methods that make writing tests a bit easier.
   */

  /**
   * Finds link with specified locator.
   *
   * @param string $locator
   *   Link id, title, text or image alt.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The link node element.
   */
  public function findLink($locator) {
    return $this->getSession()->getPage()->findLink($locator);
  }

  /**
   * Confirms absence of link with specified locator.
   *
   * @param string $locator
   *   Link id, title, text or image alt.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The link node element.
   */
  public function findNoLink($locator) {
    return empty($this->getSession()->getPage()->hasLink($locator));
  }

  /**
   * Finds field (input, textarea, select) with specified locator.
   *
   * @param string $locator
   *   Input id, name or label.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The input field element.
   */
  public function findField($locator) {
    return $this->getSession()->getPage()->findField($locator);
  }

  /**
   * Finds button with specified locator.
   *
   * @param string $locator
   *   Button id, value or alt.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The button node element.
   */
  public function findButton($locator) {
    return $this->getSession()->getPage()->findButton($locator);
  }

  /**
   * Presses button with specified locator.
   *
   * @param string $locator
   *   Button id, value or alt.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function pressButton($locator) {
    $this->getSession()->getPage()->pressButton($locator);
  }

  /**
   * Fills in field (input, textarea, select) with specified locator.
   *
   * @param string $locator
   *   Input id, name or label.
   * @param string $value
   *   Value.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @see \Behat\Mink\Element\NodeElement::setValue
   */
  public function fillField($locator, $value) {
    $this->getSession()->getPage()->fillField($locator, $value);
  }

  /**
   * Checks checkbox with specified locator.
   *
   * @param string $locator input id, name or label
   *
   * @throws ElementNotFoundException
   */
  public function checkField($locator) {
    $this->getSession()->getPage()->checkField($locator);
  }

  /**
   * Unchecks checkbox with specified locator.
   *
   * @param string $locator input id, name or label
   *
   * @throws ElementNotFoundException
   */
  public function uncheckField($locator) {
    $this->getSession()->getPage()->uncheckField($locator);
  }

  /**
   * Selects option from select field with specified locator.
   *
   * @param string  $locator  input id, name or label
   * @param string  $value    option value
   * @param Boolean $multiple select multiple options
   *
   * @throws ElementNotFoundException
   *
   * @see NodeElement::selectOption
   */
  public function selectFieldOption($locator, $value, $multiple = false) {
    $this->getSession()->getPage()->selectFieldOption($locator, $value, $multiple);
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object to check.
   *
   * @return bool
   */
  protected function drupalUserIsLoggedIn(AccountInterface $account) {
    // @TODO: This is a temporary hack for the test login fails when setting $cookie_domain.
    if (!isset($account->session_id)) {
      return (bool) $account->id();
    }
    // The session ID is hashed before being stored in the database.
    // @see \Drupal\Core\Session\SessionHandler::read()
    return (bool) db_query("SELECT sid FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = :sid", array(':sid' => Crypt::hashBase64($account->session_id)))->fetchField();
  }

  /**
   * Login a user on a specific domain.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to log the user into.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to login.
   */
  public function domainLogin(DomainInterface $domain, AccountInterface $account) {
    // Due to a quirk in session handling that we cannot directly access, it
    // works if we login, then logout, and then login to a specific domain.
    $this->drupalLogin($account);
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Login.
    $url = $domain->getPath() . 'user/login';
    $this->submitForm([
      'name' => $account->getUsername(),
      'pass' => $account->passRaw,
    ], t('Log in'));

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($account), 'User successfully logged in.');

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
