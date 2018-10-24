<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Component\Utility\Html;

/**
 * Tests the domain CSS configuration.
 *
 * @group domain
 */
class DomainCSSTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(['bartik']);
  }

  /**
   * Tests the handling of an inbound request.
   */
  public function testDomainNegotiator() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);

    // The test runner doesn't use a theme that contains the preprocess hook,
    // so set to use Bartik.
    $config = $this->config('system.theme');
    $config->set('default', 'bartik')->save();

    // Test the response of the default home page.
    foreach (\Drupal::entityTypeManager()->getStorage('domain')->loadMultiple() as $domain) {
      $this->drupalGet($domain->getPath());
      $text = '<body class="' . Html::getClass($domain->id() . '-class');
      $this->assertNoRaw($text, 'No custom CSS present.');
    }
    // Set the css classes.
    $config = $this->config('domain.settings');
    $config->set('css_classes', '[domain:machine-name]-class')->save();

    // Test the response of the default home page.
    foreach (\Drupal::entityTypeManager()->getStorage('domain')->loadMultiple() as $domain) {
      // The render cache trips up this test. In production, it may be
      // necessary to add the url.site cache context. See README.md.
      drupal_flush_all_caches();
      $this->drupalGet($domain->getPath());
      $text = '<body class="' . Html::getClass($domain->id() . '-class');
      $this->assertRaw($text, 'Custom CSS present.' . $text);
    }

    // Set the css classes.
    $config = $this->config('domain.settings');
    $config->set('css_classes', '[domain:machine-name]-class [domain:name]-class')->save();
    // Test the response of the default home page.
    foreach (\Drupal::entityTypeManager()->getStorage('domain')->loadMultiple() as $domain) {
      // The render cache trips up this test. In production, it may be
      // necessary to add the url.site cache context. See README.md.
      drupal_flush_all_caches();
      $this->drupalGet($domain->getPath());
      $text = '<body class="' . Html::getClass($domain->id() . '-class') . ' ' . Html::getClass($domain->label() . '-class');
      $this->assertRaw($text, 'Custom CSS present.' . $text);
    }

  }

}
