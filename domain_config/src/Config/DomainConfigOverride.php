<?php

/**
 * @file
 * Contains \Drupal\domain_config\Config\DomainConfigOverride.
 */

namespace Drupal\domain_config\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines domain configuration overrides.
 */
class DomainConfigOverride extends StorableConfigBase {

  use DomainConfigCollectionNameTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a domain override object.
   *
   * @param string $name
   *   The name of the configuration object being overridden.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A storage controller object to use for reading and writing the
   *   configuration override.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct($name, StorageInterface $storage, TypedConfigManagerInterface $typed_config, EventDispatcherInterface $event_dispatcher) {
    $this->name = $name;
    $this->storage = $storage;
    $this->typedConfigManager = $typed_config;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = false) {
    // @todo Use configuration schema to validate.
    //   https://drupal.org/node/2270399
    // Perform basic data validation.
    foreach ($this->data as $key => $value) {
      $this->validateValue($key, $value);
    }
    $this->storage->write($this->name, $this->data);
    // Invalidate the cache tags not only when updating, but also when creating,
    // because a domain config override object uses the same cache tag as the
    // default configuration object. Hence creating a domain override is like
    // an update of configuration, but only for a specific domain.
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = FALSE;
    // @TODO: Dispath our own event?
    # $this->eventDispatcher->dispatch(DomainConfigOverrideEvents::SAVE_OVERRIDE, new DomainConfigOverrideCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->data = array();
    $this->storage->delete($this->name);
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = TRUE;
    // @TODO: Dispath our own event?
    # $this->eventDispatcher->dispatch(DomainConfigOverrideEvents::DELETE_OVERRIDE, new DomainConfigOverrideCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Returns the id of this domain override.
   *
   * @return string
   *   The domain id.
   */
  public function getDomainId() {
    return $this->getDomainIdFromCollectionName($this->getStorage()->getCollectionName());
  }

}
