<?php

namespace Drupal\domain\EventSubscriber;

use Drupal\domain\Access\DomainAccessCheck;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainRedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Domain storage handler service.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\domain\Access\DomainAccessCheck $access_check
   *   The access check interface.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, EntityTypeManagerInterface $entity_type_manager, DomainAccessCheck $access_check, AccountInterface $account) {
    $this->domainNegotiator = $negotiator;
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $this->entityTypeManager->getStorage('domain');
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
   *
   * @see domain_alias_domain_request_alter
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    // Negotiate the request and set domain context.
    /** @var \Drupal\domain\DomainInterface $domain */
    if ($domain = $this->domainNegotiator->getActiveDomain(TRUE)) {
      $hostname = $domain->getHostname();
      $domain_url = $domain->getUrl();
      if ($domain_url) {
        $redirect_status = $domain->getRedirect();
        $path = trim($event->getRequest()->getPathInfo(), '/');
        // If domain negotiation asked for a redirect, issue it.
        if (is_null($redirect_status) && $this->accessCheck->checkPath($path)) {
          // Else check for active domain or inactive access.
          /** @var \Drupal\Core\Access\AccessResult $access */
          $access = $this->accessCheck->access($this->account);
          // If the access check fails, reroute to the default domain.
          // Note that Allowed, Neutral, and Failed are the options here.
          // We insist on Allowed.
          if (!$access->isAllowed()) {
            /** @var \Drupal\domain\DomainInterface $default */
            $default = $this->domainStorage->loadDefaultDomain();
            $domain_url = $default->getUrl();
            $redirect_status = 302;
            $hostname = $default->getHostname();
          }
        }
      }
      if (!empty($redirect_status)) {
        // Pass a redirect if necessary.
        if (DomainRedirectResponse::checkTrustedHost($hostname)) {
          $response = new TrustedRedirectResponse($domain_url, $redirect_status);
        }
        else {
          // If the redirect is not to a registered hostname, reject the
          // request.
          $response = new Response('The provided host name is not a valid redirect.', 401);
        }
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to fire very early in the stack, before accounts are cached.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestDomain', 50];
    return $events;
  }

}
