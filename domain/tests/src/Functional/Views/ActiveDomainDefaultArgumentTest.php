<?php

namespace Drupal\Tests\domain\Functional\Views;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Tests the active_domain default argument.
 *
 * @group domain
 */
class ActiveDomainDefaultArgumentTest extends DomainTestBase {

  use DomainTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'domain', 'domain_access', 'domain_test_views'];

  /**
   * Data mapping.
   *
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();
    $this->domainCreateTestDomains(3);
    $this->createTestData();
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestData() {
    foreach ($this->getDomains() as $domain_id => $domain) {
      $nodes_count = random_int(1, 5);
      while ($nodes_count !== 0) {
        $node = $this->drupalCreateNode([
          'type' => 'article',
          'title' => $this->randomString(),
          DOMAIN_ACCESS_FIELD => $domain_id,
        ]);
        $this->data[$domain_id][] = $node->id();
        $nodes_count--;
      }
    }
  }

  /**
   * Tests active_domain default argument.
   */
  public function testActiveDomainDefaultArgument() {
    $url = Url::fromRoute('view.test_active_domain_argument.page_1');

    foreach ($this->getDomains() as $domain_id => $domain) {
      $page_url = $domain->buildUrl($url->toString());
      $this->drupalGet($page_url);

      $expected_nids = array_values($this->data[$domain_id]);
      $this->assertNids($domain_id, $expected_nids);
    }
  }

  /**
   * Ensures that a list of nodes appear on the page.
   *
   * @param string $domain_id
   *   Domain ID.
   * @param array $expected_nids
   *   An array of node IDs.
   */
  protected function assertNids($domain_id, array $expected_nids = []) {
    $result = $this->xpath("//td[contains(@class, 'views-field-nid')]");
    $actual_nids = [];
    foreach ($result as $element) {
      $actual_nids[] = $element->getText();
    }

    $this->assertSame($expected_nids, $actual_nids, 'Domain ID: ' . $domain_id);
  }

}
