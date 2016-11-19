<?php

namespace Drupal\Tests\domain_access\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the domain access field element.
 *
 * @group domain_access
 */
class DomainAccessElementTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'domain_access', 'field', 'field_ui', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Run the install hook.
    // @TODO: figure out why this is necessary.
    module_load_install('domain_access');
    domain_access_install();

    // Create 5 domains.
    $this->domainCreateTestDomains(5);
  }

  /**
   * Basic test setup.
   */
  public function testDomainAccessElement() {
    $admin = $this->createDomainAdmin();
    domain_access_confirm_fields('node', 'article');
    $this->drupalLogin($admin);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $account = $this->drupalCreateUser(array('create article content', 'publish to any assigned domain'));

  }

}
