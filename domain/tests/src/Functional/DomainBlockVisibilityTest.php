<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests the domain navigation block.
 *
 * @group domain
 */
class DomainBlockVisibilityTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'block'];

  /**
   * Test domain navigation block.
   */
  public function testDomainBlockVisibility() {
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    // Place the nav block.
    $block = $this->drupalPlaceBlock('domain_nav_block');

    // Let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['use domain nav block']);

    // Load the homepage. All links should appear.
    foreach ($domains as $domain) {
      $url = $domain->getPath();
      $this->drupalGet($url);
      $this->findLinks($domains);
    }

  }

  public function findLinks(array $domains) {
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->label());
    }
  }

}
