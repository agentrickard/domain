<?php

/**
 * @file
 * Definition of Drupal\domain\EventSubscriber\DomainSubscriber.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Implements DomainSubscriber
 */
class DomainSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  protected $domainLoader;

  protected $accessCheck;

  protected $account;

  /**
   * Constructs a DomainSubscriber object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param Drupal\Core\Access\AccessCheckInterface
   *   The access check interface.
   * @param \Drupal\Core\Session\AccountInterface
   *   The current user account.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, DomainLoaderInterface $loader, AccessCheckInterface $access_check, AccountInterface $account) {
    $this->domainNegotiator = $negotiator;
    $this->domainLoader = $loader;
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
      $domain_url = $domain->getUrl();
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
          // If the access check fails, reroute to the default domain.
          // Note that Allowed, Neutral, and Failed are the options here.
          // We insist on Allowed.
          if (!$access->isAllowed()) {
            $default = $this->domainLoader->loadDefaultDomain();
            $domain_url = $default->getUrl();
            $redirect = TRUE;
            $redirect_type = 302;
          }
        }
      }
      if ($redirect) {
        // Pass a redirect if necessary.
        $response = new TrustedRedirectResponse($domain_url, $redirect_type);
        $event->setResponse($response);
      }
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    // This needs to fire very early in the stack, before accounts are cached.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain', 50);
    return $events;
  }

}
