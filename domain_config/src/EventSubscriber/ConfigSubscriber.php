<?php

/**
 * @file
 * Contains \Drupal\domain_config\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\domain_config\EventSubscriber;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if default domain has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $loader;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainCreator object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    // @TODO The purpose here is not clear, and how does it apply to Domain?
    // @see Drupal\language\EventSubscriber\ConfigSubscriber
    $saved_config = $event->getConfig();
    // Trigger a container rebuild on the next request by deleting compiled
    // from PHP storage.
    PhpStorageFactory::get('service_container')->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    return $events;
  }

}
