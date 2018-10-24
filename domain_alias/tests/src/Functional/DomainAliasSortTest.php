<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests domain alias request sorting.
 *
 * @group domain_alias
 */
class DomainAliasSortTest extends DomainAliasTestBase {

  /**
   * Tests the sorting of alias records.
   */
  public function testAliasSort() {
    $list = $this->sortList();

    $storage = \Drupal::entityTypeManager()->getStorage('domain_alias');
    foreach ($list as $key => $values) {
      $patterns = $storage->getPatterns($key);
      $this->assertTrue(empty(array_diff($values, $patterns)), 'Pattern matched as expected for ' . $key);
    }
  }

  /**
   * An array of expected matches to specific domains.
   */
  private function sortList() {
    return [
      'example.com' => [
        'example.com',
        'example.*',
        '*.com',
      ],
      'one.example.com' => [
        'one.example.com',
        'one.example.*',
        '*.example.com',
        'one.*.com',
        '*.example.*',
        '*.*.com',
        'one.*.*',
      ],
      'one.two.example.com' => [
        'one.two.example.com',
        '*.two.example.com',
        'one.two.example.*',
        'one.*.example.com',
        '*.*.example.com',
        'one.two.*.com',
        'one.*.example.*',
        'one.*.*.com',
        '*.two.*.com',
        'one.two.*.*',
        '*.*.example.com',
        '*.*.*.com',
        'one.*.*.*',
        '*.two.*.*',
      ],
      'example.com:80' => [
        'example.com:80',
        'example.com',
        'example.*',
        'example.com:*',
        'example.*:80',
        'example.*:*',
        '*.com',
        '*.com:80',
        '*.com:*',
      ],
      'example.com:8080' => [
        'example.com:8080',
        'example.com:*',
        'example.*:8080',
        'example.*:*',
        '*.com:8080',
        '*.com:*',
      ],
    ];
  }

}
