<?php

/**
 * @file
 * Contains \Drupal\domain_config\Config\DomainConfigFactoryOverrideInterface.
 */

namespace Drupal\domain_config\Config;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\domain\DomainInterface;

/**
 * Defines the interface for a configuration factory domain override object.
 */
interface DomainConfigFactoryOverrideInterface extends ConfigFactoryOverrideInterface {

  /**
   * Gets the domain object used to override configuration data.
   *
   * @return \Drupal\domain\DomainInterface
   *   The domain object used to override configuration data.
   */
  public function getDomain();

  /**
   * Sets the domain to be used in configuration overrides.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object used to override configuration data.
   *
   * @return $this
   */
  public function setDomain(DomainInterface $domain = NULL);

  /**
   * Sets the domain to be used in configuration overrides from the default.
   *
   * @param \Drupal\domain\DomainInterface $domain_default
   *   The default domain.
   *
   * @return $this
   */
  public function setDomainFromDefault(DomainInterface $domain_default = NULL);

  /**
   * Get domain override for given domain and configuration name.
   *
   * @param string $id
   *   Domain id string. (e.g. example_com).
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\Core\Config\Config
   *   Configuration override object.
   */
  public function getOverride($id, $name);

  /**
   * Returns the storage instance for a particular domain.
   *
   * @param string $id
   *   Domain id string. (e.g. example_com).
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage instance for a particular langcode.
   */
  public function getStorage($id);

  /**
   * Installs available domain configuration overrides for a given langcode.
   *
   * @param string $id
   *   Domain id string. (e.g. example_com).
   */
  public function installDomainOverrides($id);

}
