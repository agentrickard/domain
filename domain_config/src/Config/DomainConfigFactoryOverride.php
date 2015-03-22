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
   * so that the cache of storages per langcode is used.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * An array of configuration storages keyed by langcode.
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
   * The domain object used to override configuration data.
   *
   * @var \Drupal\Core\Domain\DomainInterface
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
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config) {
    $this->baseStorage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if ($this->domain) {
      $storage = $this->getStorage($this->domain->getId());
      return $storage->readMultiple($names);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverride($langcode, $name) {
    $storage = $this->getStorage($langcode);
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
  public function getStorage($langcode) {
    if (!isset($this->storages[$langcode])) {
      $this->storages[$langcode] = $this->baseStorage->createCollection($this->createConfigCollectionName($langcode));
    }
    return $this->storages[$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->domain ? $this->domain->getId() : NULL;
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
  public function setDomainFromDefault(DomainDefault $domain_default = NULL) {
    $this->domain = $domain_default ? $domain_default->get() : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function installDomainOverrides($langcode) {
    /** @var \Drupal\Core\Config\ConfigInstallerInterface $config_installer */
    $config_installer = \Drupal::service('config.installer');
    $config_installer->installCollectionDefaultConfig($this->createConfigCollectionName($langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $langcode = $this->getLangcodeFromCollectionName($collection);
    return $this->getOverride($langcode, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function addCollections(ConfigCollectionInfo $collection_info) {
    foreach (\Drupal::domainManager()->getDomains() as $domain) {
      $collection_info->addCollection($this->createConfigCollectionName($domain->getId()), $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach (\Drupal::domainManager()->getDomains() as $domain) {
      $config_translation = $this->getOverride($domain->getId(), $name);
      if (!$config_translation->isNew()) {
        $this->filterOverride($config, $config_translation);
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
    foreach (\Drupal::domainManager()->getDomains() as $domain) {
      $config_translation = $this->getOverride($domain->getId(), $old_name);
      if (!$config_translation->isNew()) {
        $saved_config = $config_translation->get();
        $storage = $this->getStorage($domain->getId());
        $storage->write($name, $saved_config);
        $config_translation->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach (\Drupal::domainManager()->getDomains() as $domain) {
      $config_translation = $this->getOverride($domain->getId(), $name);
      if (!$config_translation->isNew()) {
        $config_translation->delete();
      }
    }
  }

}
