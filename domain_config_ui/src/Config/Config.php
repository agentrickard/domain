<?php

namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\Config as CoreConfig;

/**
 * Extend core Config class to save domain specific configuration.
 */
class Config extends CoreConfig {
  /**
   * List of config that should always be saved globally.
   */
  const GLOBAL_CONFIG = [
    'core.extension',
  ];

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    // Remember original config name.
    $originalName = $this->name;

    try {
      // Get domain config name for saving.
      $domainConfigName = $this->getDomainConfigName();

      // If config is new and we are currently saving domain specific configuration,
      // save with original name first so that there is always a default configuration.
      if ($this->isNew && $domainConfigName != $originalName) {
        parent::save($has_trusted_data);
      }

      // Switch to use domain config name and save.
      $this->name = $domainConfigName;
      parent::save($has_trusted_data);
    }
    catch (\Exception $e) {
      // Reset back to original config name if save fails and re-throw.
      $this->name = $originalName;
      throw $e;
    }

    // Reset back to original config name after saving.
    $this->name = $originalName;

    return $this;
  }

  /**
   * Get the domain config name.
   */
  protected function getDomainConfigName() {
    // Return original name if reserved as global configuration.
    if (in_array($this->name, self::GLOBAL_CONFIG)) {
      return $this->name;
    }

    // If user hasn't specified how to save config, save with original name.
    if (!isset($_SESSION['domain_config_ui']['config_save_mode']) || $_SESSION['domain_config_ui']['config_save_mode'] == 'all') {
      return $this->name;
    }

    // Build prefix and add to front of existing key.
    if ($domain = \Drupal::service('domain.negotiator')->getActiveDomain(TRUE) ) {
      $prefix = 'domain.config.' . $domain->id() . '.';
      if ($language = \Drupal::languageManager()->getCurrentLanguage()) {
        $prefix .= $language->getId() . '.';
      }
      return $prefix . $this->name;
    }

    // Return current name by default.
    return $this->name;
  }
}
