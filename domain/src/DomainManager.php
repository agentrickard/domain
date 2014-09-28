<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManager.
 */

namespace Drupal\domain;

use Drupal\domain\DomainManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

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
   * The typed config handler.
   *
   * @var Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typed_config;

  /**
   * Constructs a DomainManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TypedConfigManagerInterface $typed_config) {
    $this->httpHost = NULL;
    $this->domain = NULL;
    $this->moduleHandler = $module_handler;
    $this->typedConfig = $typed_config;
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
      $this->moduleHandler->alter('domain_request', $info);
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

  public function getSchema() {
    $fields = $typedConfig->getDefinition('domain.record.*');
    return $fields['mapping'];
  }

}
