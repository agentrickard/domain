<?php

/**
 * @file
 * Definition of Drupal\domain\DomainSubscriber.
 */

namespace Drupal\domain;

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
  public function DomainLoad(GetResponseEvent $event) {
    // @TODO remove this debug code
    drupal_set_message('Domain: subscribed');
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('DomainLoad', 9999);
    return $events;
  }

}
