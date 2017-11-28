<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the weight element of the domain list builder.
 *
 * @group domain
 */
class DomainListWeightTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 10 domains.
    $this->domainCreateTestDomains(10);
  }

  /**
   * Basic test setup.
   */
  public function testDomainWeight() {
    // Test the default sort values. Should be 1 to 10.
    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      $this->assert($domain->getWeight() == $i, 'Weight set to ' . $i);
      $i++;
    }
    // The last domain should be nine_example_com.
    $this->assert($domain->id() == 'nine_example_com', 'Last domain is nine' . $domain->id());

    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ));
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Set one weight to 11.
    $locator = 'edit-domains-one-example-com-weight';
    $this->fillField($locator, 11);

    // Save the form.
    $this->pressButton('edit-submit');

    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      $this->assert($domain->getWeight() == $i, 'Weight set to ' . $i);
      $i++;
    }
    // The last domain should be one_example_com.
    $this->assert($domain->id() == 'one_example_com', 'Last domain is one'  . $domain->id());
  }
}
