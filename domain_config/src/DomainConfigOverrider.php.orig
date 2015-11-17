<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigOverrider.
 */

namespace Drupal\domain_config;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainInterface;

/**
 * Domain-specific config overrides.
 */
class DomainConfigOverrider implements ConfigFactoryOverrideInterface {
  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

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
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration storage service.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ConfigFactory $config_factory, StorageInterface $storage) {
    $this->domainNegotiator = $negotiator;
    $this->configFactory = $config_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    static $domain;
    $overrides = array();
    // loadOverrides() runs on config entities, which means that if we try
    // to run this routine on our own data, then we end up in an infinite loop.
    // So ensure that we are _not_ looking up a domain.record.*.
    $check = current($names);
    $list = explode('.', $check);
    if (isset($list[0]) && isset($list[1]) && $list[0] == 'domain' && $list[1] == 'record') {
      return $overrides;
    }
    // Only look up the domain record once, if possible.
    if (!isset($domain)) {
      $domain = $this->domainNegotiator->getActiveDomain();
    }
    if (!empty($domain)) {
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

