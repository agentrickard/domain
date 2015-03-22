<?php

/**
 * @file
 * Contains \Drupal\domain_config\Config\DomainConfigFactoryOverrideInterface.
 */

namespace Drupal\domain_config\Config;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Defines the interface for a configuration factory domain override object.
 */
interface DomainConfigFactoryOverrideInterface extends ConfigFactoryOverrideInterface {

  /**
   * Gets the domain object used to override configuration data.
   *
   * @return \Drupal\Core\Domain\DomainInterface
   *   The domain object used to override configuration data.
   */
  public function getDomain();

  /**
   * Sets the domain to be used in configuration overrides.
   *
   * @param \Drupal\Core\Domain\DomainInterface $domain
   *   The domain object used to override configuration data.
   *
   * @return $this
   */
  public function setDomain(DomainInterface $domain = NULL);

  /**
   * Sets the domain to be used in configuration overrides from the default.
   *
   * @param \Drupal\Core\Domain\DomainDefault $domain_default
   *   The default domain.
   *
   * @return $this
   */
  public function setDomainFromDefault(DomainDefault $domain_default = NULL);

  /**
   * Get domain override for given domain and configuration name.
   *
   * @param string $langcode
   *   Domain code.
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\Core\Config\Config
   *   Configuration override object.
   */
  public function getOverride($langcode, $name);

  /**
   * Returns the storage instance for a particular langcode.
   *
   * @param string $langcode
   *   Domain code.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage instance for a particular langcode.
   */
  public function getStorage($langcode);

  /**
   * Installs available domain configuration overrides for a given langcode.
   *
   * @param string $langcode
   *   Domain code.
   */
  public function installDomainOverrides($langcode);

}
