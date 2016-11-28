<?php

namespace Drupal\domain_config_ui;

use Drupal\domain\DomainInterface;
use Drupal\domain_config\DomainConfigOverrider;

/**
 * Allows admin user to switch which site is being configured.
 */
class DomainConfigUIOverrider extends DomainConfigOverrider {
  /**
   * {@inheritDoc}
   * @see \Drupal\domain_config\DomainConfigOverrider::initiateContext()
   */
  protected function initiateContext() {
    // Prevent infinite lookups by caching the request. Since the _construct()
    // is called for each lookup, this is more efficient.
    static $context;
    if ($context) {
      return;
    }
    $context++;

    parent::initiateContext();

    // Use the selected domain instead of the active domain.
    $selected_domain_id = isset($_SESSION['domain_config_ui']['config_save_domain']) ? $_SESSION['domain_config_ui']['config_save_domain'] : 'all';
    if ($selected_domain_id && $selected_domain = \Drupal::service('domain.loader')->load($selected_domain_id)) {
      $this->domain = $selected_domain;
    }
  }
}
