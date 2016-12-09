<?php

namespace Drupal\domain_config_ui;

use Drupal\domain\DomainNegotiator;
use Drupal\domain_config\DomainConfigOverrider;

/**
 * {@inheritdoc}
 */
class DomainConfigUINegotiator extends DomainNegotiator {
  /**
   * @var DomainConfigOverrider
   */
  protected $domainConfigOverrider;

  /**
   * Determine the active domain.
   */
  protected function negotiateActiveDomain() {
    // Set http host to be that of the selected domain to configure.
    if ($selected_domain = $this->getSelectedDomain()) {
      $httpHost = $selected_domain->getHostname();
    }
    else {
      $httpHost = $this->negotiateActiveHostname();
    }
    $this->setRequestDomain($httpHost);
    return $this->domain;
  }

  /**
   * Get the selected domain.
   */
  public function getSelectedDomain() {
    $selected_domain_id = $this->getSelectedDomainId();
    if ($selected_domain_id && $selected_domain = $this->domainLoader->load($selected_domain_id)) {
      return $selected_domain;
    }
  }

  /**
   * Get the selected domain ID.
   */
  public function getSelectedDomainId() {
    // Return selected domain ID on admin paths only.
    return !empty($_SESSION['domain_config_ui']['config_save_domain']) ? $_SESSION['domain_config_ui']['config_save_domain'] : '';
  }

  /**
   * Set the current selected domain ID.
   *
   * @param string $domain_id
   */
  public function setSelectedDomain($domain_id) {
    if ($domain = $this->domainLoader->load($domain_id)) {
      // Set session for subsequent request.
      $_SESSION['domain_config_ui']['config_save_domain'] = $domain_id;
      // Switch active domain now so that selected domain configuration can be loaded immediatly.
      // This is primarily for switching domain with AJAX request.
      $this->domainConfigOverrider->setDomain($domain);
    }
    else {
      $_SESSION['domain_config_ui']['config_save_domain'] = '';
    }
  }

  /**
   * Set the domain config overrider.
   *
   * @param DomainConfigOverrider $domain_config_overrider
   */
  public function setDomainConfigOverrider(DomainConfigOverrider $domain_config_overrider) {
    $this->domainConfigOverrider = $domain_config_overrider;
  }
}
