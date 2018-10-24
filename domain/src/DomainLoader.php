<?php

namespace Drupal\domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Loads Domain records.
 *
 * @deprecated
 *  This class will be removed before the 8.1.0 release.
 *  Use DomainStorage instead, loaded through the EntityTypeManager.
 */
class DomainLoader implements DomainLoaderInterface {

  /**
   * The typed config handler.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DomainLoader object.
   *
   * Trying to inject the storage manager throws an exception.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @see getStorage()
   */
  public function __construct(TypedConfigManagerInterface $typed_config, ConfigFactoryInterface $config_factory) {
    $this->typedConfig = $typed_config;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSchema() {
    $fields = $this->typedConfig->getDefinition('domain.record.*');
    return isset($fields['mapping']) ? $fields['mapping'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function load($id, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache([$id]);
    }
    return $controller->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultId() {
    $result = $this->loadDefaultDomain();
    if (!empty($result)) {
      return $result->id();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultDomain() {
    $result = $this->getStorage()->loadByProperties(['is_default' => TRUE]);
    if (!empty($result)) {
      return current($result);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache($ids);
    }
    return $controller->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleSorted(array $ids = NULL) {
    $domains = $this->loadMultiple($ids);
    uasort($domains, [$this, 'sort']);
    return $domains;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    $hostname = $this->prepareHostname($hostname);
    $result = $this->getStorage()->loadByProperties(['hostname' => $hostname]);
    if (empty($result)) {
      return NULL;
    }
    return current($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadOptionsList() {
    $list = [];
    foreach ($this->loadMultipleSorted() as $id => $domain) {
      $list[$id] = $domain->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function sort(DomainInterface $a, DomainInterface $b) {
    return $a->getWeight() > $b->getWeight();
  }

  /**
   * Loads the storage controller.
   *
   * We use the loader very early in the request cycle. As a result, if we try
   * to inject the storage container, we hit a circular dependency. Using this
   * method at least keeps our code easier to update.
   */
  protected function getStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareHostname($hostname) {
    // Strip www. prefix off the hostname.
    $ignore_www = $this->configFactory->get('domain.settings')->get('www_prefix');
    if ($ignore_www && substr($hostname, 0, 4) == 'www.') {
      $hostname = substr($hostname, 4);
    }
    return $hostname;
  }

}
