<?php

/**
 * @file
 * Definition of Drupal\domain\EventSubscriber\DomainSubscriber.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
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
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  protected $accessCheck;

  protected $account;

  /**
   * Constructs a DomainSubscriber object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param Drupal\Core\Access\AccessCheckInterface
   *   The access check interface.
   * @param \Drupal\Core\Session\AccountInterface
   *   The current user account.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, AccessCheckInterface $access_check, AccountInterface $account) {
    $this->domainNegotiator = $negotiator;
    $this->accessCheck = $access_check;
    $this->account = $account;
  }

  /**
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    $redirect = FALSE;
    if ($domain = $this->domainNegotiator->negotiateActiveDomain()) {
      $domain_url = $domain->get('url');
      if ($domain_url) {
        $redirect_type = $domain->getRedirect();
        $path = trim($event->getRequest()->getPathInfo(), '/');
        // If domain negotiation asked for a redirect, issue it.
        if (!is_null($redirect_type)) {
          $redirect = TRUE;
        }
        // Else check for active domain or inactive access.
        elseif ($apply = $this->accessCheck->checkPath($path)) {
          $access = $this->accessCheck->access($this->account);
          if ($access->isForbidden()) {
            $redirect = TRUE;
          }
        }
      }
      if ($redirect) {
        // Pass a redirect if necessary.
        $response = new RedirectResponse($domain_url, $redirect_type);
        $event->setResponse($response);
      }
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
