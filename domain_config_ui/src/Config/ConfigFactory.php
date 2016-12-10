<?php
namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;
use Drupal\domain_config_ui\Config\Config;
use Drupal\domain_config_ui\DomainConfigUIManager;

/**
 * Overrides Drupal\Core\Config\ConfigFactory in order to use our own Config class.
 */
class ConfigFactory extends CoreConfigFactory {
  /**
   * List of config that can be saved for a specific domain.
   * Use * for wildcards.
   */
  protected $allowedDomainConfig = [
    'system.site',
    'system.theme*',
    '*.theme.*',
    '*.settings',
    'node.settings',
  ];

  /**
   * The Domain config UI manager.
   *
   * @var DomainConfigUIManager
   */
  protected $domainConfigUIManager;

  /**
   * Helper to check if config is allowed to be saved for domain.
   *
   * @param string $name
   */
  protected function isAllowedDomainConfig($name) {
    // Get default allowed config and allow other modules to alter.
    $allowed = $this->allowedDomainConfig;
    \Drupal::moduleHandler()->alter('domain_config_allowed', $allowed);

    // Return original name if reserved not allowed.
    $is_allowed = FALSE;
    foreach ($allowed as $config_name) {
      // Convert config_name into into regex.
      // Escapes regex syntax, but keeps * wildcards.
      $pattern = '/^' . str_replace('\*', '.*', preg_quote($config_name, '/')) . '$/';
      if (preg_match($pattern, $name)) {
        $is_allowed = TRUE;
      }
    }

    return $is_allowed;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::createConfigObject()
   */
  protected function createConfigObject($name, $immutable) {
    if (!$immutable && $this->isAllowedDomainConfig($name)) {
      $config = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
      // Pass the UI manager to the Config object.
      $config->setDomainConfigUIManager($this->domainConfigUIManager);
      return $config;
    }
    return parent::createConfigObject($name, $immutable);
  }

  /**
   * Set the Domain config UI manager.
   *
   * @param DomainConfigUIManager $domain_config_ui_manager
   */
  public function setDomainConfigUIManager($domain_config_ui_manager) {
    $this->domainConfigUIManager = $domain_config_ui_manager;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::doLoadMultiple()
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    // Let parent load multiple load as usual.
    $list = parent::doLoadMultiple($names, $immutable);

    // Do not apply overrides if configuring 'all' domains or config is immutable.
    if (empty($this->domainConfigUIManager) || !$this->domainConfigUIManager->getSelectedDomainId() || !$this->isAllowedDomainConfig(current($names))) {
      return $list;
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = [];
      $storage_data = $this->storage->readMultiple($names);

      // Load module overrides so that domain specific config is loaded in admin forms.
      if (!empty($storage_data)) {
        // Only get domain overrides if we have configuration to override.
        $module_overrides = $this->loadDomainOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getConfigCacheKey($name, $immutable);

        if (isset($module_overrides[$name])) {
          $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          $list[$name] = $this->cache[$cache_key];
        }

        $this->propagateConfigOverrideCacheability($cache_key, $name);
      }
    }

    return $list;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::doGet()
   */
  protected function doGet($name, $immutable = TRUE) {
    // Do not apply overrides if configuring 'all' domains or config is immutable.
    if (empty($this->domainConfigUIManager) || !$this->domainConfigUIManager->getSelectedDomainId() || !$this->isAllowedDomainConfig($name)) {
      return parent::doGet($name, $immutable);
    }

    if ($config = $this->doLoadMultiple([$name], $immutable)) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object.
      $config = $this->createConfigObject($name, $immutable);

      // Load domain overrides so that domain specific config is loaded in admin forms.
      $overrides = $this->loadDomainOverrides([$name]);
      if (isset($overrides[$name])) {
        $config->setModuleOverride($overrides[$name]);
      }

      foreach ($this->configFactoryOverrides as $override) {
        $config->addCacheableDependency($override->getCacheableMetadata($name));
      }

      return $config;
    }
  }

  /**
   * Get arbitrary overrides for the named configuration objects from Domain module.
   *
   * @param array $names
   *   The names of the configuration objects to get overrides for.
   *
   * @return array
   *   An array of overrides keyed by the configuration object name.
   */
  protected function loadDomainOverrides(array $names) {
    return $this->domainConfigUIManager->loadOverrides($names);
  }
}
