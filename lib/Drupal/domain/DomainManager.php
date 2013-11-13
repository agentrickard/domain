<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManager.
 */

namespace Drupal\domain;

use Drupal\domain\DomainManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class DomainManager implements DomainManagerInterface {

  public $httpHost;

  public $domain;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->httpHost = NULL;
    $this->domain = NULL;
    $this->moduleHandler = $module_handler;
  }

  public function setRequestDomain($httpHost) {
    $this->setHttpHost($httpHost);
    $domain = domain_load_hostname($httpHost);
    // If a straight load fails, check with modules (like Domain Alias) that
    // register alternate paths with the main module.
    if (empty($domain)) {
      $domain = entity_create('domain', array('hostname' => $httpHost));
      // @TODO: Should this be an event instead?
      // @TODO: Should this be hook_domain_bootstrap_lookup?
      $info['domain'] = $domain;
      \Drupal::moduleHandler()->alter('domain_request', $info);
      $domain = $info['domain'];
    }
    if (!empty($domain->id)) {
      $this->setActiveDomain($domain);
    }
  }

  public function setActiveDomain(DomainInterface $domain) {
    $this->domain = $domain;
  }

  public function getActiveDomain() {
    return $this->domain;
  }

  public function setHttpHost($httpHost) {
    $this->httpHost = $httpHost;
  }

  public function getHttpHost() {
    return $this->httpHost;
  }

}
