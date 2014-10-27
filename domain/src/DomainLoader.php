<?php

/**
 * @file
 * Definition of Drupal\domain\DomainLoader.
 */

namespace Drupal\domain;

use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

class DomainLoader implements DomainLoaderInterface {

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
   * Constructs a DomainLoader object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TypedConfigManagerInterface $typed_config) {
    $this->moduleHandler = $module_handler;
    $this->typedConfig = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSchema() {
    $fields = $this->typedConfig->getDefinition('domain.record.*');
    return isset($fields['mapping']) ? $fields['mapping'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultId() {
    $result = entity_load_multiple_by_properties('domain', array('is_default' => TRUE));
    if (!empty($result)) {
      return key($result);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultDomain() {
    $result = entity_load_multiple_by_properties('domain', array('is_default' => TRUE));
    if (!empty($result)) {
      return current($result);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple($ids = NULL, $reset = FALSE) {
    return entity_load_multiple('domain', $ids, $reset);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleSorted($ids = NULL) {
    $domains = $this->loadMultiple();
    uasort($domains, array($this, 'sort'));
    return $domains;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    $entities = entity_load_multiple_by_properties('domain', array('hostname' => $hostname));
    if (empty($entities)) {
      return FALSE;
    }
    return current($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadOptionsList() {
    $list = array();
    foreach ($this->loadMultipleSorted() as $id => $domain) {
      $list[$id] = $domain->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($a, $b) {
    return $a->getWeight() > $b->getWeight();
  }

}
