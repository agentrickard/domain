<?php

namespace Drupal\domain_config;

use Drupal\domain\DomainInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The domain context of the request.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $domain;

  /**
   * The language context of the request.
   *
   * @var \Drupal\Core\Language\LanguageInterface
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
   * Indicates that the request context is set.
   *
   * @var bool
   */
  protected $contextSet;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(StorageInterface $storage, ModuleHandlerInterface $module_handler) {
    $this->storage = $storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    // Try to prevent repeating lookups.
    static $lookups;
    // Key should be a known length, so hash.
    $key = md5(implode(':', $names));
    if (isset($lookups[$key])) {
      return $lookups[$key];
    }

    // Set the context of the override request.
    if (empty($this->contextSet)) {
      $this->initiateContext();
    }

    // Prepare our overrides.
    $overrides = [];
    // loadOverrides() runs on config entities, which means that if we try
    // to run this routine on our own data, then we end up in an infinite loop.
    // So ensure that we are _not_ looking up a domain.record.*.
    $check = current($names);
    $list = explode('.', $check);
    if (isset($list[0]) && isset($list[1]) && $list[0] == 'domain' && $list[1] == 'record') {
      $lookups[$key] = $overrides;
      return $overrides;
    }
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

        // Apply any settings.php overrides.
        if (isset($GLOBALS['config'][$config_name['langcode']])) {
          $overrides[$name] = $GLOBALS['config'][$config_name['langcode']];
        }
        elseif (isset($GLOBALS['config'][$config_name['domain']])) {
          $overrides[$name] = $GLOBALS['config'][$config_name['domain']];
        }
      }
      $lookups[$key] = $overrides;
    }

    return $overrides;
  }

  /**
   * Get configuration name for this hostname.
   *
   * It will be the same name with a prefix depending on domain and language:
   * @code domain.config.DOMAIN_ID.LANGCODE @endcode
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
    if (empty($this->contextSet)) {
      $this->initiateContext();
    }
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
    // Prevent infinite lookups by caching the request. Since the _construct()
    // is called for each lookup, this is more efficient.
    $this->contextSet = TRUE;

    // We must ensure that modules have loaded, which they may not have.
    // See https://www.drupal.org/project/domain/issues/3025541.
    $this->moduleHandler->loadAll();

    // Get the language context. Note that injecting the language manager
    // into the service created a circular dependency error, so we load from
    // the core service manager.
    $this->languageManager = \Drupal::languageManager();
    $this->language = $this->languageManager->getCurrentLanguage();

    // The same issue is true for the domainNegotiator.
    $this->domainNegotiator = \Drupal::service('domain.negotiator');
    // Get the domain context.
    $this->domain = $this->domainNegotiator->getActiveDomain(TRUE);
  }

}
