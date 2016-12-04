<?php

namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\Config as CoreConfig;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Extend core Config class to save domain specific configuration.
 */
class Config extends CoreConfig {
  /**
   * List of config that should always be saved globally.
   * Use * for wildcards.
   */
  const GLOBAL_CONFIG = [
    'core.extension',
    'domain.record.*',
    'domain_alias.*',
  ];

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Set the Domain negotiator.
   * @param DomainNegotiatorInterface $domain_negotiator
   */
  public function setDomainNegotiator(DomainNegotiatorInterface $domain_negotiator) {
    $this->domainNegotiator = $domain_negotiator;
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
    // Get default global config and allow other modules to alter.
    $global_config = self::GLOBAL_CONFIG;
    \Drupal::moduleHandler()->alter('domain_config_global_config', $global_config);

    // Return original name if reserved as global configuration.
    foreach ($global_config as $config_name) {
      // Convert config_name into into regex.
      // Escapes regex all syntax, but keeps * wildcard.
      $pattern = '/^' . str_replace('\*', '.*', preg_quote($config_name, '/')) . '$/';
      if (preg_match($pattern, $this->name)) {
        return $this->name;
      }
    }

    // Build prefix and add to front of existing key.
    if ($selected_domain = $this->domainNegotiator->getSelectedDomain()) {
      $prefix = 'domain.config.' . $selected_domain->id() . '.';
      if ($language = \Drupal::languageManager()->getCurrentLanguage()) {
        $prefix .= $language->getId() . '.';
      }
      return $prefix . $this->name;
    }

    // Return current name by default.
    return $this->name;
  }
}
