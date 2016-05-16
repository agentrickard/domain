<?php

namespace Drupal\domain\EventSubscriber;

use Drupal\domain\Access\DomainAccessCheck;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the domain context for an http request.
 */
class DomainSubscriber implements EventSubscriberInterface {

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The domain loader service.
   *
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoader;

  /**
   * The core access check service.
   *
   * @var \Drupal\Core\Access\AccessCheckInterface
   */
  protected $accessCheck;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a DomainSubscriber object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\Access\DomainAccessCheck $access_check
   *   The access check interface.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, DomainLoaderInterface $loader, DomainAccessCheck $access_check, AccountInterface $account) {
    $this->domainNegotiator = $negotiator;
    $this->domainLoader = $loader;
    $this->accessCheck = $access_check;
    $this->account = $account;
  }

  /**
   * Sets the domain context of the request.
   *
   * This method also determines the redirect status for the http request.
   *
   * Specifically, here we determine if a redirect is required. That happens
   * in one of two cases: an unauthorized request to an inactive domain is made;
   * a domain alias is set to redirect to its primary domain record.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    $redirect = FALSE;
    // Negotiate the request and set domain context.
    /** @var \Drupal\domain\DomainInterface $domain */
    if ($domain = $this->domainNegotiator->getActiveDomain(TRUE)) {
      $domain_url = $domain->getUrl();
      if ($domain_url) {
        $redirect_type = $domain->getRedirect();
        $path = trim($event->getRequest()->getPathInfo(), '/');
        // If domain negotiation asked for a redirect, issue it.
        if (!is_null($redirect_type)) {
          $redirect = TRUE;
        }
        // Else check for active domain or inactive access.
        elseif ($this->accessCheck->checkPath($path)) {
          /** @var \Drupal\Core\Access\AccessResult $access */
          $access = $this->accessCheck->access($this->account);
          // If the access check fails, reroute to the default domain.
          // Note that Allowed, Neutral, and Failed are the options here.
          // We insist on Allowed.
          if (!$access->isAllowed()) {
            /** @var \Drupal\domain\DomainInterface $default */
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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to fire very early in the stack, before accounts are cached.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain', 50);
    return $events;
  }

}
