<?php

/**
 * @file
 * Definition of Drupal\domain\DomainNegotiator.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DomainNegotiator implements DomainNegotiatorInterface {

  /**
   * The HTTP_HOST value of the request.
   */
  protected $httpHost;

  /**
   * The domain record returned by the lookup request.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $domain;

  /**
   * The loader class.
   *
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoader;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, DomainLoaderInterface $loader) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->domainLoader = $loader;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestDomain($httpHost, $reset = FALSE) {
    // @TODO: Investigate caching methods.
    $this->setHttpHost($httpHost);
    if ($domain = $this->domainLoader->loadByHostname($httpHost)) {
      // If the load worked, set an exact match flag for the hook.
      $domain->setMatchType(DOMAIN_MATCH_EXACT);
    }
    // Fallback to default domain if no match.
    elseif ($domain = $this->domainLoader->loadDefaultDomain()) {
      $domain->setMatchType(DOMAIN_MATCH_NONE);
    }
    // If a straight load fails, create a base domain for checking. This data
    // is required for hook_domain_request_alter().
    else {
      $values = array('hostname' => $httpHost);
      $domain = \Drupal::entityManager()->getStorage('domain')->create($values);
      $domain->setMatchType(DOMAIN_MATCH_NONE);
    }
    // Now check with modules (like Domain Alias) that register alternate
    // lookup systems with the main module.
    $this->moduleHandler->alter('domain_request', $domain);

    // We must have registered a valid id, else the request made no match.
    $id = $domain->id();
    if (!empty($id)) {
      $this->setActiveDomain($domain);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveDomain(DomainInterface $domain) {
    // @TODO: caching
    $this->domain = $domain;
  }

  /**
   * Determine the active domain.
   */
  protected function negotiateActiveDomain() {
    $httpHost = $this->negotiateActiveHostname();
    $this->setRequestDomain($httpHost);
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDomain($reset = FALSE) {
    if ($reset) {
      $this->negotiateActiveDomain();
    }
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveId() {
    return $this->domain->id();
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateActiveHostname() {
    if ($request = $this->requestStack->getCurrentRequest()) {
      $httpHost = $request->getHttpHost();
    }
    else {
      $httpHost = $_SERVER['HTTP_HOST'];
    }
    return !empty($httpHost) ? $httpHost : 'localhost';
  }

  /**
   * {@inheritdoc}
   */
  public function setHttpHost($httpHost) {
    $this->httpHost = $httpHost;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHost() {
    return $this->httpHost;
  }

}
