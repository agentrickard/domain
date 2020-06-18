<?php

namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\Config as CoreConfig;
use Drupal\domain_config_ui\DomainConfigUIManager;

/**
 * Extend core Config class to save domain specific configuration.
 */
class Config extends CoreConfig {

  /**
   * The Domain config UI manager.
   *
   * @var \Drupal\domain_config_ui\DomainConfigUIManager
   */
  protected $domainConfigUIManager;

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
  public function save($has_trusted_data = FALSE) {
    // Remember original config name.
    $originalName = $this->name;

    try {
      // Get domain config name for saving.
      $domainConfigName = $this->getDomainConfigName();

      // If config is new and we are saving domain specific configuration,
      // save with original name so there is always a default configuration.
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
    // Return selected config name.
    return $this->domainConfigUIManager->getSelectedConfigName($this->name);
  }

}
