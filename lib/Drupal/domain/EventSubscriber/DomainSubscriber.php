<?php

/**
 * @file
 * Definition of Drupal\domain\EventSubscriber\DomainSubscriber.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal;
use Drupal\domain\DomainManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Implements DomainSubscriber
 */
class DomainSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\domain\DomainManagerInterface
   */
  protected $domainManager;

  /**
   * Constructs a DomainSubscriber object.
   *
   * @param \Drupal\domain\DomainManagerInterface $domain_manager
   *   The domain manager service.
   */
  public function __construct(DomainManagerInterface $domain_manager) {
    $this->domainManager = $domain_manager;
  }

  /**
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    $request = $event->getRequest();
    // TODO: Pass $url string or the entire Request?
    $httpHost = $request->getHttpHost();
    $this->domainManager->setRequestDomain($httpHost);
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    // Returns multiple times. Should be CONTROLLER?
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain', 100);
    return $events;
  }

}
