<?php

namespace Drupal\Tests\domain_content\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Base class and helper methods for testing domain content.
 */
abstract class DomainContentTestBase extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain', 'domain_content'];

  /**
   * An array of domains.
   *
   * @var \Drupal\domain\DomainInterface
   */
  public $domains;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create five test domains.
    $this->domainCreateTestDomains(5);

    $this->domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
  }

  /**
   * Creates dummy content for testing.
   *
   * 25 nodes, 5 per domain and 5 to all affiliates.
   */
  public function createDomainContent() {
    foreach ($this->domains as $id => $domain) {
      for ($i = 0; $i < 5; $i++) {
        $this->drupalCreateNode([
          'type' => 'article',
          DOMAIN_ACCESS_FIELD => [$id],
          DOMAIN_ACCESS_ALL_FIELD => ($id == 'one_example_com') ? 1 : 0,
        ]);
      }
    }
    // Rebuild node access rules.
    node_access_rebuild();
  }

  /**
   * Creates dummy content for testing.
   *
   * 25 users, 5 per domain and 5 to all affiliates.
   */
  public function createDomainUsers() {
    foreach ($this->domains as $id => $domain) {
      for ($i = 0; $i < 5; $i++) {
        $account[$id] = $this->drupalCreateUser([
          'access administration pages',
          'access domain content',
          'access domain content editors',
          'publish to any domain',
          'assign editors to any domain',
        ]);
        $this->addDomainsToEntity('user', $account[$id]->id(), $id, DOMAIN_ACCESS_FIELD);
        if ($id == 'one_example_com') {
          $this->addDomainsToEntity('user', $account[$id]->id(), 1, DOMAIN_ACCESS_ALL_FIELD);
        }
      }
    }
  }

  /**
   * Strips whitespace from a page response and runs assertRaw() equivalent.
   *
   * In tests, we were having difficulty with spacing in tables. This method
   * takes some concepts from Mink and rearranges them to work for our tests.
   * Notably, we don't pull page content from the session request.
   *
   * @param string $content
   *   The generated HTML, such as from drupalGet().
   * @param string $text
   *   The text string to search for.
   */
  public function checkContent($content, $text) {
    // Convert all whitespace to spaces.
    $content = preg_replace('/\s+/u', ' ', $content);
    // Strip all whitespace between tags.
    $content = preg_replace('@>\\s+<@', '><', $content);
    $regex = '/' . preg_quote($text, '/') . '/ui';
    $message = sprintf('The text "%s" was found in the text of the current page.', $text);
    $this->assert((bool) preg_match($regex, $content), $message);
  }

}
