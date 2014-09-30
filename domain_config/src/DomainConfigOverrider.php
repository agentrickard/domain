<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigOverrider.
 */

namespace Drupal\domain_config;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\domain\DomainResolverInterface;
use Drupal\domain\DomainInterface;

/**
 * Domain-specific config overrides.
 */
class DomainConfigOverrider implements ConfigFactoryOverrideInterface {
  /**
   * The domain manager.
   *
   * @var \Drupal\domain\DomainResolverInterface
   */
  protected $domainManager;

  /**
   * The configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\domain\DomainResolverInterface $domain_manager
   *   The domain manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration storage service.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   */
  public function __construct(DomainResolverInterface $domain_manager, ConfigFactory $config_factory, StorageInterface $storage) {
    $this->domainManager = $domain_manager;
    $this->configFactory = $config_factory;
    $this->storage = $storage;
  }
  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    // @TODO: language handling?
    // @TODO: caching?
    if ($domain = $this->domainManager->getActiveDomain()) {
      foreach ($names as $name) {
        $config_name = $this->getDomainConfigName($name, $domain);
        // Check to see if the config storage has an appropriately named file
        // containing override data.
        if ($override = $this->storage->read($config_name)) {
          $overrides[$name] = $override;
        }
      }
    }
    return $overrides;
  }

  /**
   * Get configuration name for this hostname.
   *
   * It will be the same name with a prefix depending on domain:
   * domain.config.DOMAIN.ID
   *
   * @param string $name
   *   The name of the config object.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object.
   *
   * @return string
   *   The domain-specific config name.
   */
  public function getDomainConfigName($name, DomainInterface $domain) {
    return 'domain.config.' . $domain->id() . '.' . $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'DomainConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}

