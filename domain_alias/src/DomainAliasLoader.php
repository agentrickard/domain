<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasLoader.
 */

namespace Drupal\domain_alias;

use Drupal\domain_alias\DomainAliasLoaderInterface;
use Drupal\domain_alias\DomainAliasInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Alias loader utility class.
 */
class DomainAliasLoader implements DomainAliasLoaderInterface {

  /**
   * The typed config handler.
   *
   * @var Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typed_config;

  /**
   * Constructs a DomainAliasLoader object.
   *
   * Trying to inject the storage manager throws an exception.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   *
   * @see getStorage()
   */
  public function __construct(TypedConfigManagerInterface $typed_config) {
    $this->typedConfig = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSchema() {
    $fields = $this->typedConfig->getDefinition('domain_alias.alias.*');
    return isset($fields['mapping']) ? $fields['mapping'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function load($id, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache(array($id));
    }
    return $controller->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple($ids = NULL, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache($ids);
    }
    return $controller->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    $parts = explode('.', $hostname);
    $patterns = array($hostname);
    $patterns[] = $parts[0] . '.*';
    $count = count($parts);
    // Build the list of possible matching patterns.
    for ($i = 0; $i < $count; $i++) {
      $temp = $parts;
      $temp[$i] = '*';
      $patterns[] = implode('.', $temp);
    }
    // Pattern lists are sorted based on the fewest wildcards. That gives us
    // more precise matches first.
    $patterns[] = '*.' . $hostname;
    $patterns[] = $hostname . '.*';
    uasort($patterns, array($this, 'sort'));
    foreach ($patterns as $pattern) {
      if ($alias = $this->loadByPattern($pattern)) {
        return $alias;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByPattern($pattern) {
    $result = $this->getStorage()->loadByProperties(array('pattern' => $pattern));
    if (empty($result)) {
      return NULL;
    }
    return current($result);
  }

  /**
   * {@inheritdoc}
   */
  public function sort($a, $b) {
    // @TODO: Test this logic.
    if (substr_count($a, '*') > 0) {
      return 1;
    }
    return 0;
  }

  /**
   * Loads the storage controller.
   *
   * We use the loader very early in the request cycle. As a result, if we try
   * to inject the storage container, we hit a circular dependency. Using this
   * method at least keeps our code easier to update.
   */
  protected function getStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('domain_alias');
    return $storage;
  }

}
