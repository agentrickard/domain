<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests the domain block visibility condition.
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
    $allowed_domains = [
      'example_com' => 'example_com',
      'one_example_com' => 'one_example_com',
    ];
    $settings = [
      'visibility' => [
        'domain' => [
          'id' => 'domain',
          'domains' => $allowed_domains,
          'negate' => FALSE,
          'context_mapping' => ['domain' => '@domain.current_domain_context:domain'],
        ],
      ],
    ];
    $block2 = $this->placeBlock('domain_nav_block', $settings);

    // Load the homepage. All links should appear.
    foreach ($domains as $id => $domain) {
      $url = $domain->getPath();
      $this->drupalGet($url);
      if (in_array($id, $allowed_domains, TRUE)) {
        $this->assertBlockAppears($block2);
      }
      else {
        $this->assertNoBlockAppears($block2);
      }
    }

    // Now let's negate (reverse) the condition.
    $settings['visibility']['domain']['negate'] = TRUE;
    $block3 = $this->placeBlock('domain_nav_block', $settings);

    // Load the homepage. All links should appear.
    foreach ($domains as $id => $domain) {
      $url = $domain->getPath();
      $this->drupalGet($url);
      if (!in_array($id, $allowed_domains, TRUE)) {
        $this->assertBlockAppears($block3);
      }
      else {
        $this->assertNoBlockAppears($block3);
      }
    }

  }

}
