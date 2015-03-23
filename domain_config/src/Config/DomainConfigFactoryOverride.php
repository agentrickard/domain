<?php

/**
 * @file
 * Contains \Drupal\domain_config\Config\DomainConfigFactoryOverride.
 */

namespace Drupal\domain_config\Config;

use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigFactoryOverrideBase;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\domain_config\Config\DomainConfigFactoryOverrideInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Provides domain overrides for the configuration factory.
 */
class DomainConfigFactoryOverride extends ConfigFactoryOverrideBase implements DomainConfigFactoryOverrideInterface, EventSubscriberInterface {

  use DomainConfigCollectionNameTrait;

  /**
   * The configuration storage.
   *
   * Do not access this directly. Should be accessed through self::getStorage()
   * so that the cache of storages per domain is used.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * An array of configuration storages keyed by domain.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $storages;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $loader;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The domain object used to override configuration data.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $domain;

  /**
   * Constructs the DomainConfigFactoryOverride object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config, DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->baseStorage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if ($this->domain) {
      $storage = $this->getStorage($this->domain->id());
      return $storage->readMultiple($names);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverride($id, $name) {
    $storage = $this->getStorage($id);
    $data = $storage->read($name);

    $override = new DomainConfigOverride(
      $name,
      $storage,
      $this->typedConfigManager,
      $this->eventDispatcher
    );

    if (!empty($data)) {
      $override->initWithData($data);
    }
    return $override;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($id) {
    if (!isset($this->storages[$id])) {
      $this->storages[$id] = $this->baseStorage->createCollection($this->createConfigCollectionName($id));
    }
    return $this->storages[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->domain ? $this->domain->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function setDomain(DomainInterface $domain = NULL) {
    $this->domain = $domain;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDomainFromDefault(DomainInterface $domain_default = NULL) {
    $this->domain = $domain_default ? $domain_default->get() : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function installDomainOverrides($id) {
    /** @var \Drupal\Core\Config\ConfigInstallerInterface $config_installer */
    $config_installer = \Drupal::service('config.installer');
    $config_installer->installCollectionDefaultConfig($this->createConfigCollectionName($id));
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $id = $this->getDomainIdFromCollectionName($collection);
    return $this->getOverride($id, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function addCollections(ConfigCollectionInfo $collection_info) {
    foreach ($this->loader->loadMultiple() as $domain) {
      $collection_info->addCollection($this->createConfigCollectionName($domain->id()), $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach ($this->loader->loadMultiple() as $domain) {
      $config_domain = $this->getOverride($domain->id(), $name);
      if (!$config_domain->isNew()) {
        $this->filterOverride($config, $config_domain);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    $old_name = $event->getOldName();
    foreach ($this->loader->loadMultiple() as $domain) {
      $config_domain = $this->getOverride($domain->id(), $old_name);
      if (!$config_domain->isNew()) {
        $saved_config = $config_domain->get();
        $storage = $this->getStorage($domain->id());
        $storage->write($name, $saved_config);
        $config_domain->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach ($this->loader->loadMultiple() as $domain) {
      $config_domain = $this->getOverride($domain->id(), $name);
      if (!$config_domain->isNew()) {
        $config_domain->delete();
      }
    }
  }

}
