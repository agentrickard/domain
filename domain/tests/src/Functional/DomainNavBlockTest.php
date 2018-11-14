<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests the domain navigation block.
 *
 * @group domain
 */
class DomainNavBlockTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'node', 'block'];

  /**
   * Test domain navigation block.
   */
  public function testDomainNav() {
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    // Place the nav block.
    $block = $this->drupalPlaceBlock('domain_nav_block');

    // Let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['use domain nav block']);

    // Load the homepage. All links should appear.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->label());
    }

    // Disable one of the domains. One link should not appear.
    $disabled = $domains['one_example_com'];
    $disabled->disable();

    // Load the homepage.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      if ($id != 'one_example_com') {
        $this->findLink($domain->label());
      }
      else {
        $this->assertNoRaw($domain->label());
      }
    }
    // Let the anon user view diabled domains. All links should appear.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access inactive domains']);

    // Load the homepage.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->label());
    }

    // Now update the configuration and test again.
    $this->config('block.block.' . $block->id())
      ->set('settings.link_options', 'active')
      ->set('settings.link_label', 'hostname')
      ->save();

    // Load the the login page.
    $this->drupalGet('user/login');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->getHostname());
      $this->assertRaw($domain->buildUrl('/user/login'));
    }

    // Now update the configuration and test again.
    $this->config('block.block.' . $block->id())
      ->set('settings.link_options', 'home')
      ->set('settings.link_theme', 'menu')
      ->set('settings.link_label', 'url')
      ->save();

    // Load the the login page.
    $this->drupalGet('user/login');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->getPath());
      $this->assertRaw($domain->getPath());
    }
  }

}
