<?php

namespace Drupal\Tests\domain\Functional;

//use Drupal\Tests\domain\Functional\DomainCommandTestBase;

/**
 * Tests the domain record creation API.
 *
 * @group domain
 */
class DomainDrushCommandsTest extends DomainCommandTestBase {

  /**
   * Show some info about the domain module environment.
   */
  public function testInfoDomains() {
    if ($this->isWindows()) {
      $this->markTestSkipped('Site-set not currently available on Windows.');
    }
    $this->setUpDrupal(1, true);

    // Assure that a pending post-update is reported.
    $this->drush('domain:info', [], ['format' => 'json']);
    $out = $this->getOutputFromJSON('domain_access_entities');
    $this->assertEquals('1', $out);
  }


  /**
   * List the domains present on the system and some of their properties.
   */
  public function testListDomains() {
    if ($this->isWindows()) {
      $this->markTestSkipped('Site-set not currently available on Windows.');
    }
    $this->setUpDrupal(1, true);

    // Assure that a pending post-update is reported.
    $this->drush('domain:list', [], ['format' => 'json']);
    $out = $this->getOutputFromJSON('devel-post-null_op');
    $this->assertEquals('1', $out->domain_access_entities);
  }

  /**
   * List the domains present on the system and some of their properties.
   */
  public function testAddDomain() {
    if ($this->isWindows()) {
      $this->markTestSkipped('Site-set not currently available on Windows.');
    }
    $this->setUpDrupal(1, true);

    // Assure that a pending post-update is reported.
    $this->drush('domain:add', [ 'my.domain.example.com', 'my_domain_example_com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('devel-post-null_op');
    $this->assertEquals('1', $out->domain_access_entities);
  }

  /**
   * List the domains present on the system and some of their properties.
   */
  public function testSetDefaultDomain() {
    if ($this->isWindows()) {
      $this->markTestSkipped('Site-set not currently available on Windows.');
    }
    $this->setUpDrupal(1, true);

    // Assure that a pending post-update is reported.
    $this->drush('domain:add', [ 'my.domain.example.com', 'my_domain_example_com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('devel-post-null_op');
    $this->assertEquals('1', $out);

    // Assure that a pending post-update is reported.
    $this->drush('domain:list', [ '--fields=name,is_default', 'my.domain.example.com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('is_default');
    $this->assertEquals('1', $out);
  }

  /**
   * List the domains present on the system and some of their properties.
   */
  public function testNameDomain() {
    if ($this->isWindows()) {
      $this->markTestSkipped('Site-set not currently available on Windows.');
    }
    $this->setUpDrupal(1, true);

    // Assure that a pending post-update is reported.
    $this->drush('domain:add', [ 'my.domain.example.com', 'my_domain_example_com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('devel-post-null_op');
    $this->assertEquals('1', $out);

    // Assure that a pending post-update is reported.
    $this->drush('domain:list', [ '--fields=name,is_default', 'my.domain.example.com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('is_default');
    $this->assertEquals('1', $out);

    // Assure that a pending post-update is reported.
    $this->drush('domain:name', ['my_domain_example_com', 'my_domain_example_com'], ['format' => 'json']);
    $out = $this->getOutputFromJSON('is_default');
    $this->assertEquals('1', $out);
  }

}