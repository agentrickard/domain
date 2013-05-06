<?php

/**
 * @file
 * Definition of Drupal\domain\EventSubscriber\DomainSubscriber.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Implements DomainSubscriber
 */
class DomainSubscriber implements EventSubscriberInterface {

  /**
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    $request = $event->getRequest();
    // TODO: Pass $url string or the entire Request?
    $httpHost = $request->getHttpHost();
    $uri = $request->getRequestUri();
    $domain = Drupal::service('domain.manager');
    $domain->requestDomain($httpHost);
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    // Returns multiple times. Should be CONTROLLER?
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain');
    return $events;
  }

}
