<?php

namespace Drupal\Tests\domain\Functional;

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
  public static $modules = ['domain', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 60 domains. We paginate at 50.
    $this->domainCreateTestDomains(60);
  }

  /**
   * Basic test setup.
   */
  public function testDomainWeight() {
    // Test the default sort values. Should be 1 to 60.
    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      $this->assertEquals($i, $domain->getWeight(), 'Weight set to ' . $i);
      $i++;
    }
    // The last domain should be test59_example_com.
    $this->assertEquals('test59_example_com', $domain->id(), 'Last domain is test59');
    $domains_old = $domains;

    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Set one weight to 61.
    $locator = 'edit-domains-one-example-com-weight';
    $this->fillField($locator, 61);

    // Save the form.
    $this->pressButton('edit-submit');

    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      // Weights should be the same one page 1 except for the one we changed.
      if ($domain->id() == 'one_example_com') {
        $this->assertEquals(61, $domain->getWeight(), 'Weight set to 61 ' . $domain->getWeight());
      }
      else {
        $this->assertEquals($domains_old[$domain->id()]->getWeight(), $domain->getWeight(), 'Weights unchanged');
      }
      $i++;
    }
    // The last domain should be one_example_com.
    $this->assertEquals('one_example_com', $domain->id(), 'Last domain is one');

    // Go to page two.
    $this->clickLink('Next');
    $this->assertSession()->statusCodeEquals(200);
    // Set one weight to 2.
    $locator = 'edit-domains-one-example-com-weight';
    $this->fillField($locator, 2);
    // Save the form.
    $this->pressButton('edit-submit');

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Go to page two.
    $this->clickLink('Next');
    $this->assertSession()->statusCodeEquals(200);

    // Check the domain sort order.
    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      if ($domain->id() == 'one_example_com') {
        $this->assertEquals(2, $domain->getWeight(), 'Weight set to 2');
      }
      else {
        $this->assertEquals($domains_old[$domain->id()]->getWeight(), $domain->getWeight(), 'Weights unchanged');
      }
    }
    // The last domain should be test59_example_com.
    $this->assertEquals('test59_example_com', $domain->id(), 'Last domain is test59' . $domain->id());
  }

}
