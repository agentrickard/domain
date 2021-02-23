<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests the domain navigation block.
 *
 * @group domain
 */
class DomainBlockVisibilityTest extends DomainTestBase {

  use AssertBlockAppearsTrait;
  use BlockCreationTrait;

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
    $block = $this->placeBlock('domain_nav_block');

    // Let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['use domain nav block']);

    // Load the homepage. All links should appear.
    foreach ($domains as $domain) {
      $url = $domain->getPath();
      $this->drupalGet($url);
      $this->assertBlockAppears($block);
    }

    // Now let's only show the block on two domains.

    // Now let's negate (reverse) the condition.
  }

}
