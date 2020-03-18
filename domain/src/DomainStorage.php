<?php

namespace Drupal\domain;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Loads Domain records.
 */
class DomainStorage extends ConfigEntityStorage implements DomainStorageInterface {

  /**
   * The typed config handler.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * Constructs a DomainStorage object.
   *
   * Trying to inject the storage manager throws an exception.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, TypedConfigManagerInterface $typed_config) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->typedConfig = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('config.typed')
    );
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
    $result = $this->loadByProperties(['is_default' => TRUE]);
    if (!empty($result)) {
      return current($result);
    }
    return NULL;
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
    $result = $this->loadByProperties(['hostname' => $hostname]);
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
    // Prioritize the weights.
    $weight_difference = $a->getWeight() - $b->getWeight();
    if ($weight_difference !== 0) {
      return $weight_difference;
    }

    // Fallback to the labels if the weights are equal.
    return strcmp($a->label(), $b->label());
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

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $default = $this->loadDefaultId();
    $domains = $this->loadMultiple();
    if (empty($values)) {
      $values['hostname'] = $this->createHostname();
      $values['name'] = \Drupal::config('system.site')->get('name');
    }
    $values += [
      'scheme' => $this->getDefaultScheme(),
      'status' => 1,
      'weight' => count($domains) + 1,
      'is_default' => (int) empty($default),
    ];
    $domain = parent::create($values);

    return $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function createHostname() {
    // We cannot inject the negotiator due to dependencies.
    return \Drupal::service('domain.negotiator')->negotiateActiveHostname();
  }

  /**
   * {@inheritdoc}
   */
  public function createMachineName($hostname = NULL) {
    if (empty($hostname)) {
      $hostname = $this->createHostname();
    }
    return preg_replace('/[^a-z0-9_]/', '_', $hostname);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultScheme() {
    // Use the foundation request if possible.
    $request = \Drupal::request();
    if (!empty($request)) {
      $scheme = $request->getScheme();
    }
    // Else use the server variable.
    elseif (!empty($_SERVER['https'])) {
      $scheme = 'https';
    }
    // Else fall through to default.
    else {
      $scheme = 'http';
    }
    return $scheme;
  }

}
