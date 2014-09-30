<?php

/**
 * @file
 * Definition of Drupal\domain\DomainResolver.
 */

namespace Drupal\domain;

use Drupal\domain\DomainResolverInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

class DomainResolver implements DomainResolverInterface {

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
   * Constructs a DomainResolver object.
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

  /**
   * @return array
   *   An array keyed by field name and containing the name and
   *   label for the field.
   */
  public function getSchema() {
    $fields = $this->typedConfig->getDefinition('domain.record.*');
    return isset($fields['mapping']) ? $fields['mapping'] : array();
  }

  /**
   * Returns the id of the default domain.
   *
   * @return
   *   The id of the default domain or FALSE if none is set.
   */
  public function getDefaultId() {
    // manager getDefaultId.
    $result = entity_load_multiple_by_properties('domain', array('is_default' => TRUE));
    if (!empty($result)) {
      return key($result);
    }
    return FALSE;
  }

  /**
   * Gets the default domain object.
   */
  public function getDefaultDomain() {
    $result = entity_load_multiple_by_properties('domain', array('is_default' => TRUE));
    if (!empty($result)) {
      return current($result);
    }
    return FALSE;
  }

  /**
   * Loads multiple domains.
   */
  public function loadMultiple($ids = array(), $reset = FALSE) {
    return entity_load_multiple('domain', $ids, $reset);
  }

  /**
   * Loads multiple domains and sorts by weight.
   */
  public function loadMultipleSorted($ids = array()) {
    $domains = $this->loadMultiple();
    return uasort($domains, array($this, 'sort'));
  }

  /**
   * Loads a domain record by hostname lookup.
   */
  public function loadByHostname($hostname) {
    $entities = entity_load_multiple_by_properties('domain', array('hostname' => $hostname));
    if (empty($entities)) {
      return FALSE;
    }
    return current($entities);
  }

  /**
   * Creates a new domain record object.
   */
  public function createDomain($inherit = FALSE, array $values = array()) { }

  /**
   * Gets the next numeric id for a domain.
   */
  public function getNextId() {
    $domains = $this->loadMultiple();
    $max = 0;
    foreach ($domains as $domain) {
      if ($domain->domain_id > $max) {
        $max = $domain->domain_id;
      }
    }
    return $max + 1;
  }

  /**
   * Gets the hostname of the active request.
   */
  public function getHostname() {
    return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
  }

  /**
   * Gets the machine name of a host, used as primary key.
   */
  public function getMachineName($hostname) {
    return preg_replace('/[^a-z0-9_]+/', '_', $hostname);
  }

  /**
   * Gets the id of the active domain.
   */
  public function getActiveId() {
    return $this->getActiveDomain()->id();
  }

  /**
   * Returns the list of domains formatted for a form options list.
   */
  public function optionsList() {
    $list = array();
    foreach ($this->loadMultipleSorted() as $id => $domain) {
      $list[$id] = $domain->name;
    }
    return $list;
  }

  /**
   * Sorts domains by weight.
   */
  public function sort($a, $b) {
    return $a->weight > $b->weight;
  }

  /**
   * Gets the list of required fields.
   */
  public function getRequiredFields() {
    return array('hostname', 'name', 'id', 'scheme', 'status', 'weight');
  }

}
