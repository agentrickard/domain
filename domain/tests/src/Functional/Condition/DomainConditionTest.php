<?php

namespace Drupal\Tests\domain\Functional\Condition;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests the domain condition.
 *
 * @group domain
 */
class DomainConditionTest extends DomainTestBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * A test domain.
   */
  protected $test_domain;

  /**
   * A test domain that never matches $test_domain.
   */
  protected $not_domain;

  /**
   * An array of all testing domains.
   */
  protected $domains;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set the condition manager.
    $this->manager = $this->container->get('plugin.manager.condition');

    // Create test domains.
    $this->domainCreateTestDomains(5);

    // Get two sample domains.
    $this->domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    $this->test_domain = array_shift($this->domains);
    $this->not_domain = array_shift($this->domains);
  }

  /**
   * Test the domain condition.
   */
  public function testConditions() {
    // Grab the domain condition and configure it to check against one domain.
    $condition = $this->manager->createInstance('domain')
      ->setConfig('domains', array($this->test_domain->id() => $this->test_domain->id()))
      ->setContextValue('entity:domain', $this->not_domain);
    $this->assertFalse($condition->execute(), 'Domain request condition fails on wrong domain.');

    // Grab the domain condition and configure it to check against itself.
    $condition = $this->manager->createInstance('domain')
      ->setConfig('domains', array($this->test_domain->id() => $this->test_domain->id()))
      ->setContextValue('entity:domain', $this->test_domain);
    $this->assertTrue($condition->execute(), 'Domain request condition succeeds on matching domain.');

    // Check for the proper summary.
    // Summaries require an extra space due to negate handling in summary().
    $this->assertEqual($condition->summary(), 'Active domain is ' . $this->test_domain->label());

    // Check the negated summary.
    $condition->setConfig('negate', TRUE);
    $this->assertEqual($condition->summary(), 'Active domain is not ' . $this->test_domain->label());

    // Check the negated condition.
    $this->assertFalse($condition->execute(), 'Domain request condition fails when condition negated.');
  }

}
