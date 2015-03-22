<?php

/**
 * @file
 * Contains \Drupal\domain_config\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\domain_config\EventSubscriber;

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if default domain has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The domain manager.
   *
   * @var \Drupal\Core\Domain\DomainManagerInterface
   */
  protected $domainManager;

  /**
   * The default domain.
   *
   * @var \Drupal\Core\Domain\DomainDefault
   */
  protected $domainDefault;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Domain\DomainManagerInterface $domain_manager
   *   The domain manager.
   * @param \Drupal\Core\Domain\DomainDefault $domain_default
   *   The default domain.
   */
  public function __construct(DomainManagerInterface $domain_manager, DomainDefault $domain_default) {
    $this->domainManager = $domain_manager;
    $this->domainDefault = $domain_default;
  }

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'system.site' && $event->isChanged('langcode')) {
      $domain = $this->domainManager->getDomain($saved_config->get('langcode'));
      // During an import the domain might not exist yet.
      if ($domain) {
        $this->domainDefault->set($domain);
        $this->domainManager->reset();
        domain_negotiation_url_prefixes_update();
      }
      // Trigger a container rebuild on the next request by deleting compiled
      // from PHP storage.
      PhpStorageFactory::get('service_container')->deleteAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    return $events;
  }

}
