<?php

namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\domain_config_ui\DomainConfigUIManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends core ConfigFactory class to save domain specific configuration.
 */
class ConfigFactory extends CoreConfigFactory {

  /**
   * The Domain config UI manager.
   *
   * @var Drupal\domain_config_ui\DomainConfigUIManager
   */
  protected $domainConfigUIManager;

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\domain_config_ui\DomainConfigUIManager $domain_config_ui_manager
   *   The domain config UI manager.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config, DomainConfigUIManager $domain_config_ui_manager) {
    parent::__construct($storage, $event_dispatcher, $typed_config);
    $this->domainConfigUIManager = $domain_config_ui_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function createConfigObject($name, $immutable) {
    if (!$immutable) {
      $config = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
      // Pass the UI manager to the Config object.
      $config->setDomainConfigUiManager($this->domainConfigUIManager);
      return $config;
    }
    return parent::createConfigObject($name, $immutable);
  }

  /**
   * Set the Domain config UI manager.
   *
   * @param \Drupal\domain_config_ui\DomainConfigUIManager $domain_config_ui_manager
   *   The Domain config UI manager.
   */
  public function setDomainConfigUiManager(DomainConfigUIManager $domain_config_ui_manager) {
    $this->domainConfigUIManager = $domain_config_ui_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    // Let parent load multiple load as usual.
    $list = parent::doLoadMultiple($names, $immutable);

    // Do not override if configuring 'all' domains or config is immutable.
    // @TODO: This will need to change if we allow saving for 'all allowed domains'
    if (empty($this->domainConfigUIManager) || !$this->domainConfigUIManager->getSelectedDomainId()) {
      return $list;
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = [];
      $storage_data = $this->storage->readMultiple($names);

      // Load module overrides so that domain config is loaded in admin forms.
      if (!empty($storage_data)) {
        // Only get domain overrides if we have configuration to override.
        $module_overrides = $this->loadDomainOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getConfigCacheKey($name, $immutable);

        if (isset($module_overrides[$name])) {
          $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          $list[$name] = $this->cache[$cache_key];
          $this->propagateConfigOverrideCacheability($cache_key, $name);
        }

      }
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  protected function doGet($name, $immutable = TRUE) {
    // If config for 'all' domains or immutable then don't override config.
    if (empty($this->domainConfigUIManager) || !$this->domainConfigUIManager->getSelectedDomainId()) {
      return parent::doGet($name, $immutable);
    }

    if ($config = $this->doLoadMultiple([$name], $immutable)) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object.
      $config = $this->createConfigObject($name, $immutable);

      // Load domain overrides so domain config is loaded in admin forms.
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
   * Get Domain module overrides for the named configuration objects.
   *
   * @param array $names
   *   The names of the configuration objects to get overrides for.
   *
   * @return array
   *   An array of overrides keyed by the configuration object name.
   */
  protected function loadDomainOverrides(array $names) {
    $overrides = [];
    foreach ($names as $name) {
      // Try to load the language-specific domain override.
      $config_name = $this->domainConfigUIManager->getSelectedConfigName($name);
      if ($override = $this->storage->read($config_name)) {
        $overrides[$name] = $override;
      }
      // If we tried to load a language-sensitive file and failed, load the
      // domain-specific override.
      elseif ($this->domainConfigUIManager->getSelectedLanguageId()) {
        $omit_language = TRUE;
        $config_name = $this->domainConfigUIManager->getSelectedConfigName($name, $omit_language);
        if ($override = $this->storage->read($config_name)) {
          $overrides[$name] = $override;
        }
      }
    }
    return $overrides;
  }

}
