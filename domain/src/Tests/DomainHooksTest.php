<?php

namespace Drupal\domain\Tests;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Tests the domain module hook invocations.
 *
 * @group domain
 */
class DomainHooksTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_test');

  /**
   * Tests domain loading.
   */
  public function testHookDomainLoad() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a domain.
    $this->domainCreateTestDomains();

    // Check the created domain based on it's known id value.
    $key = 'example_com';

    $domain = \Drupal::service('domain.loader')->load($key);

    // Internal hooks.
    $path = $domain->getPath();
    $url = $domain->getUrl();
    $this->assertTrue(isset($path), new FormattableMarkup('The path property was set to %path by hook_entity_load.', array('%path' => $path)));
    $this->assertTrue(isset($url), new FormattableMarkup('The url property was set to %url by hook_entity_load.', array('%url' => $url)));

    // External hooks.
    $this->assertTrue($domain->foo == 'bar', 'The foo property was set to <em>bar</em> by hook_domain_load.');

    // @TODO: test additional hooks.
  }

}
