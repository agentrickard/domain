<?php

namespace Drupal\domain_config_ui;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Domain Config UI manager.
 */
class DomainConfigUIManager implements DomainConfigUIManagerInterface {

  /**
   * A RequestStack instance.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs DomainConfigUIManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedConfigName($name, $omit_language = FALSE) {
    if ($domain_id = $this->getSelectedDomainId()) {
      $prefix = "domain.config.{$domain_id}.";
      if (!$omit_language && $langcode = $this->getSelectedLanguageId()) {
        $prefix .= "{$langcode}.";
      }
      return $prefix . $name;
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedDomainId() {
    if ($domain = $this->request->get('domain_config_ui_domain')) {
      return $domain;
    }
    if (isset($_SESSION['domain_config_ui_domain'])) {
      return $_SESSION['domain_config_ui_domain'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedLanguageId() {
    if ($language = $this->request->get('domain_config_ui_language')) {
      return $language;
    }
    if (isset($_SESSION['domain_config_ui_language'])) {
      return $_SESSION['domain_config_ui_language'];
    }
  }

}
