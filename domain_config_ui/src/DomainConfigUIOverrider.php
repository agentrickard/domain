<?php

namespace Drupal\domain_config_ui;

use Drupal\domain\DomainInterface;
use Drupal\domain_config\DomainConfigOverrider;

/**
 * Allows admin user to switch which site is being configured.
 */
class DomainConfigUIOverrider extends DomainConfigOverrider {
  /**
   * Get configuration name for this current domain being configured.
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
    if (!isset($_SESSION['domain_config_ui']['config_save_domain']) || $_SESSION['domain_config_ui']['config_save_domain'] == 'all') {
      return parent::getDomainConfigName($name, $domain);
    }
    if ($configDomain = \Drupal::service('domain.loader')->load($_SESSION['domain_config_ui']['config_save_domain'])) {
      drupal_set_message('domain.config.' . $configDomain->id() . '.' . $this->language->getId() . '.' . $name);
      return [
        'langcode' => 'domain.config.' . $configDomain->id() . '.' . $this->language->getId() . '.' . $name,
        'domain' => 'domain.config.' . $configDomain->id() . '.' . $name,
      ];
    }
    return parent::getDomainConfigName($name, $domain);
  }

}
