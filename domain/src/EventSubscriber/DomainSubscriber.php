<?php

/**
 * @file
 * Definition of Drupal\domain\EventSubscriber\DomainSubscriber.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal\domain\DomainResolverInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements DomainSubscriber
 */
class DomainSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\domain\DomainResolverInterface
   */
  protected $domainResolver;

  /**
   * Constructs a DomainSubscriber object.
   *
   * @param \Drupal\domain\DomainResolverInterface $resolver
   *   The domain resolver service.
   */
  public function __construct(DomainResolverInterface $resolver) {
    $this->domainResolver = $resolver;
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
    $this->domainResolver->setRequestDomain($httpHost);
    $domain = $this->domainResolver->resolveActiveDomain();
    // Pass a redirect if necessary.
    if (!empty($domain->getProperty('url')) && !empty($domain->redirect)) {
      $response = new RedirectResponse($domain->getProperty('url'), $domain->redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    // Returns multiple times. Should be CONTROLLER?
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain', 400);
    return $events;
  }

}
