<?php

/**
 * @file
 * Definition of Drupal\domain\DomainNegotiator.
 */

namespace Drupal\domain;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DomainNegotiator implements DomainNegotiatorInterface {

  public $httpHost;

  public $domain;

  public $domainLoader;

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
    $this->httpHost = NULL;
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->domainLoader = $loader;
    $this->domain = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestDomain($httpHost, $reset = FALSE) {
    static $loookup;
    if (isset($lookup[$httpHost]) && !$reset) {
      return $lookup[$httpHost];
    }
    $this->setHttpHost($httpHost);
    $domain = $this->domainLoader->loadByHostname($httpHost);
    // If a straight load fails, create a base domain for checking.
    if (empty($domain)) {
      $domain = entity_create('domain', array('hostname' => $httpHost));
    }
    // Now check with modules (like Domain Alias) that register alternate
    // lookup systems with the main module.
    $this->moduleHandler->alter('domain_request', $domain);

    // We must have registered a valid id, else the request made no match.
    $id = $domain->id();
    if (!empty($id)) {
      $this->setActiveDomain($domain);
    }
    // Store the result in local cache.
    $lookup[$httpHost] = $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveDomain(DomainInterface $domain) {
    // @TODO: caching
    $this->domain = $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateActiveDomain() {
    $httpHost = $this->negotiateActiveHostname();
    $this->setRequestDomain($httpHost);
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDomain($reset = FALSE) {
    if (is_null($this->domain) || $reset) {
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
