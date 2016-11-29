<?php
namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;
use Drupal\domain_config_ui\Config\Config;

/**
 * Overrides Drupal\Core\Config\ConfigFactory in order to use our own Config class.
 */
class ConfigFactory extends CoreConfigFactory {
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::createConfigObject()
   */
  protected function createConfigObject($name, $immutable) {
    if (!$immutable) {
      return new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    }
    return parent::createConfigObject($name, $immutable);
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::doLoadMultiple()
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    // Let parent load multiple load as usual.
    $list = parent::doLoadMultiple($names, $immutable);

    // Do not apply overrides if configuring 'all' domains.
    if (!\Drupal::service('domain.negotiator')->getSelectedDomainId()) {
      return $list;
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = array();
      $storage_data = $this->storage->readMultiple($names);

      // Load module overrides so that domain specific config is loaded in admin forms.
      if (!empty($storage_data)) {
        // Only get module overrides if we have configuration to override.
        $module_overrides = $this->loadOverrides($names);
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
    // Do not apply overrides if configuring 'all' domains.
    if (!\Drupal::service('domain.negotiator')->getSelectedDomainId()) {
      return parent::doGet($name, $immutable);
    }

    if ($config = $this->doLoadMultiple(array($name), $immutable)) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object.
      $config = $this->createConfigObject($name, $immutable);

      // Load module overrides so that domain specific config is loaded in admin forms.
      $overrides = $this->loadOverrides(array($name));
      if (isset($overrides[$name])) {
        $config->setModuleOverride($overrides[$name]);
      }

      // Apply any settings.php overrides.
      if ($immutable && isset($GLOBALS['config'][$name])) {
        $config->setSettingsOverride($GLOBALS['config'][$name]);
      }

      foreach ($this->configFactoryOverrides as $override) {
        $config->addCacheableDependency($override->getCacheableMetadata($name));
      }

      return $config;
    }
  }
}
