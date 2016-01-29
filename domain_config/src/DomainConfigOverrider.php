<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigOverrider.
 */

namespace Drupal\domain_config;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Domain-specific config overrides.
 *
 * @see \Drupal\language\Config\LanguageConfigFactoryOverride for ways
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
   * Drupal language manager.
   *
   * Using dependency injection for this service causes a circular dependency.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, StorageInterface $storage) {
    $this->domainNegotiator = $negotiator;
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
    $this->initiateContext();
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
  protected function getDomainConfigName($name, DomainInterface $domain) {
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
    $this->initiateContext();
    $metadata = new CacheableMetadata();
    if (!empty($this->domain)) {
      $metadata->addCacheContexts(['url.site', 'languages:language_interface']);
    }
    return $metadata;
  }

  /**
   * Sets domain and language contexts for the request.
   *
   * We wait to do this in order to avoid circular dependencies
   * with the locale module.
   */
  protected function initiateContext() {
    // @TODO: Is this cacheable?
    // Get the language context. Note that injecting the language manager
    // into the service created a circular dependency error, so we load from
    // the core service manager.
    $this->languageManager = \Drupal::languageManager();
    $this->language = $this->languageManager->getCurrentLanguage();
    // Get the domain context.
    $this->domain = $this->domainNegotiator->getActiveDomain();
  }
}
