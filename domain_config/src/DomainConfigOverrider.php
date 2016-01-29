<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigOverrider.
 */

namespace Drupal\domain_config;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Domain-specific config overrides.
 *
 * See \Drupal\language\Config\LanguageConfigFactoryOverride for ways
 * this might be improved.
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
   * The domain context of the request.
   */
  protected $domain;

  /**
   * The language context of the request.
   */
  protected $language;

  /**
   * Drupal language manager
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
    $overrides = array();
    // loadOverrides() runs on config entities, which means that if we try
    // to run this routine on our own data, then we end up in an infinite loop.
    // So ensure that we are _not_ looking up a domain.record.*.
    $check = current($names);
    $list = explode('.', $check);
    if (isset($list[0]) && isset($list[1]) && $list[0] == 'domain' && $list[1] == 'record') {
      return $overrides;
    }
    $this->initiateDomainAndLanguage();
    if (!empty($this->domain)) {
      foreach ($names as $name) {
        $config_name = $this->getDomainConfigName($name, $this->domain);
        // Check to see if the config storage has an appropriately named file
        // containing override data.
        if ($override = $this->storage->read($config_name['langcode'])) {
          $overrides[$name] = $override;
        }
        // Check to see if we have a file without a specific language.
        elseif ($override = $this->storage->read($config_name['domain'])) {
          $overrides[$name] = $override;
        }
      }
    }
    return $overrides;
  }

  /**
   * Get configuration name for this hostname.
   *
   * It will be the same name with a prefix depending on domain and language:
   * domain.config.DOMAIN_ID.LANGCODE
   *
   * @param string $name
   *   The name of the config object.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object.
   *
   * @return array
   *   The domain-language, and domain-specific config names.
   */
  public function getDomainConfigName($name, DomainInterface $domain) {
    return [
      'langcode' => 'domain.config.' . $domain->id() . '.' . $this->language->getId() . '.' . $name,
      'domain' => 'domain.config.' . $domain->id() . '.' . $name,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    $suffix = $this->domain ? $this->domain->id() : '';
    $suffix .= $this->language ? $this->language->getId() : '';
    return ($suffix) ? $suffix : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $this->initiateDomainAndLanguage();
    $metadata = new CacheableMetadata();
    if ($this->domain) {
      $metadata->addCacheContexts(['url.site', 'languages']);
    }
    return $metadata;
  }

  /**
   * Set domain and language
   * We wait to do this in order to avoid circular dependencies
   * with the locale module
   */
  private function initiateDomainAndLanguage() {
    if (empty($this->domain)) {
      // Get the language context. Note that injecting the language manager
      // into the service created a circular dependency error, so we load directly
      // from the core service manager.
      $this->languageManager = \Drupal::languageManager();
      $this->language = $this->languageManager->getCurrentLanguage();
      $this->domain = $this->domainNegotiator->getActiveDomain();
    }
  }
}

