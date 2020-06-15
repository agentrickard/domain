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
   * Note that we had issues with using Dependency Injection from the service here, so
   * instead we instantiate the request manually.
   *
   * @link https://www.drupal.org/project/domain/issues/3004243#comment-13689184
   */
  public function __construct() {
    $this->setRequest();
  }

  /**
   * Sets the current request.
   */
  private function setRequest() {
    $this->request = \Drupal::request();
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
