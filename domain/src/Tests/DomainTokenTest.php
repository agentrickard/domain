<?php

namespace Drupal\domain\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Tests the domain token handler.
 *
 * @group domain
 */
class DomainTokenTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'block');

  /**
   * Tests the handling of an inbound request.
   */
  public function testDomainTokens() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // Since we cannot read the service request, we place a block
    // which shows the current domain information.
    $this->drupalPlaceBlock('domain_server_block');

    // To get around block access, let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, array('view domain information'));

    // Test the response of the default home page.
    foreach (\Drupal::service('domain.loader')->loadMultiple() as $domain) {
      $this->drupalGet($domain->getPath());
      $this->assertRaw($domain->label(), 'Loaded the proper domain.');
      $this->assertRaw('Token values', 'Token values printed.');
      foreach ($this->tokenList() as $token => $callback) {
        $this->assertRaw("<td>$token</td>", "$token found correctly.");
        // The URL token is sensitive to the path, which is /user, but that
        // does not come across when making the callback outside of a request
        // context.
        $value = $domain->{$callback}();
        if ($token == '[domain:url]') {
          $value = str_replace('user', '', $value);
        }
        $this->assertRaw('<td>' . $value . '</td>', 'Value set correctly to ' . $value);
      }
    }
  }

  /**
   * Gets the list of tokens and value callbacks used by the test.
   *
   * @return array
   *   An array keyed by token string, with value of expected domain value.
   */
  private function tokenList() {
    $tokens = [];
    foreach (\Drupal::service('domain.token')->getCallbacks() as $key => $callback) {
      $name = "[domain:$key]";
      $tokens[$name] = $callback;
    }
    return $tokens;
  }

}
