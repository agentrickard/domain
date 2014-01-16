<?php
/**
 * @file
 * Definition of \Drupal\domain_config\EventSubscriber\DomainConfigSubscriber.
 */

namespace Drupal\domain_config\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigModuleOverridesEvent;
use Drupal\Core\Config\StorageInterface;
use Drupal\domain\DomainManagerInterface;
use Drupal\domain\DomainInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Domain Config helper
 */
class DomainConfigSubscriber implements EventSubscriberInterface {

  /**
   * The domain manager.
   *
   * @var \Drupal\domain\DomainManagerInterface
   */
  protected $domainManager;

  /**
   * The configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\domain\DomainManagerInterface $domain_manager
   *   The domain manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration storage service.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   */
  public function __construct(DomainManagerInterface $domain_manager, ConfigFactory $config_factory, StorageInterface $storage) {
    $this->domainManager = $domain_manager;
    $this->configFactory = $config_factory;
    $this->storage = $storage;
  }

  /**
   * Override configuration values with domain-specific data.
   *
   * @param \Drupal\Core\Config\ConfigModuleOverridesEvent $event
   *   The Event to process.
   */
  public function configLoad(ConfigModuleOverridesEvent $event) {
    $names = $event->getNames();
    // @TODO: language handling?
    // @TODO: caching?
    if ($domain = $this->domainManager->getActiveDomain()) {
      foreach ($names as $name) {
        $config_name = $this->getDomainConfigName($name, $domain);
        // Check to see if the config storage has an appropriately named file
        // containing override data.
        if ($override = $this->configFactory->get($config_name)) {
          $event->setOverride($name, $override);
        }
      }
    }
  }

  /**
   * Get configuration name for this hostname.
   *
   * It will be the same name with a prefix depending on domain:
   * domain.config.DOMAIN.ID
   *
   * @param string $name
   *   The name of the config object.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object.
   *
   * @return string
   *   The domain-specific config name.
   */
  public function getDomainConfigName($name, DomainInterface $domain) {
    return 'domain.config.' . $domain->id() . '.' . $name;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.module.overrides'][] = array('configLoad', 100);
    return $events;
  }
}
