<?php

/**
 * @file
 * Contains \Drupal\domain_config\Tests\DomainConfigOverrideImportTest.
 */

namespace Drupal\domain_config\Tests;

use Drupal\domain\DomainInterface;
use Drupal\domain_config\Tests\DomainConfigTestBase;

/**
 * Ensures the domain config overrides can be synchronized.
 *
 * @group domain
 */
class DomainConfigOverrideImportTest extends DomainConfigTestBase {

  /**
   * Tests that domain can be enabled and overrides are created during a sync.
   */
  public function testConfigOverrideImport() {
    // Create three domains.
    $this->domainCreateTestDomains(3);
    $domains = domain_load_multiple();
    $test_domain = array_pop($domains);
    /* @var \Drupal\Core\Config\StorageInterface $staging */
    $staging = \Drupal::service('config.storage.staging');
    $this->copyConfig(\Drupal::service('config.storage'), $staging);

    // Uninstall the domain module and its dependencies so we can test
    // enabling the domain module and creating overrides at the same time
    // during a configuration synchronization.
    \Drupal::service('module_installer')->uninstall(array('domain'));
    // Ensure that the current site has no overrides registered to the
    // ConfigFactory.
    $this->rebuildContainer();

    /* @var \Drupal\Core\Config\StorageInterface $override_staging */
    $override_staging = $staging->createCollection('domain.' . $test_domain->id());
    // Create some overrides in staging.
    $override_staging->write('system.site', array('name' => 'Test default site name'));
    $override_staging->write('system.maintenance', array('message' => 'Test message: @site is currently under maintenance. We should be back shortly. Thank you for your patience'));

    $this->configImporter()->import();
    $this->rebuildContainer();
    \Drupal::service('router.builder')->rebuild();

    $manager = \Drupal::service('domain.config_factory_override');
    $override = $manager->getOverride($test_domain->id(), 'system.site');
    $this->assertEqual('One default site name', $override->get('name'));
    $this->drupalGet($test_domain->getUrl());
    $this->assertText('One default site name');

    #$this->drupalLogin($this->rootUser);
    #$this->drupalGet('admin/config/development/maintenance/translate/fr/edit');
    #$this->assertText('FR message: @site is currently under maintenance. We should be back shortly. Thank you for your patience');
  }

}
