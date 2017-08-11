<?php

namespace Drupal\domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class DomainNegotiator implements DomainNegotiatorInterface {

  /**
   * Defines record matching types when dealing with request alteration.
   *
   * @see hook_domain_request_alter().
   */
  const DOMAIN_MATCH_NONE = 0;
  const DOMAIN_MATCH_EXACT = 1;
  const DOMAIN_MATCH_ALIAS = 2;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The Domain loader object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, DomainLoaderInterface $loader, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->domainLoader = $loader;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestDomain($httpHost, $reset = FALSE) {
    // @TODO: Investigate caching methods.
    $this->setHttpHost($httpHost);
    // Try to load a direct match.
    if ($domain = $this->domainLoader->loadByHostname($httpHost)) {
      // If the load worked, set an exact match flag for the hook.
      $domain->setMatchType(self::DOMAIN_MATCH_EXACT);
    }
    // If a straight load fails, create a base domain for checking. This data
    // is required for hook_domain_request_alter().
    else {
      $values = array('hostname' => $httpHost);
      /** @var \Drupal\domain\Entity\DomainInterface $domain */
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->setMatchType(self::DOMAIN_MATCH_NONE);
    }
    // Now check with modules (like Domain Alias) that register alternate
    // lookup systems with the main module.
    $this->moduleHandler->alter('domain_request', $domain);

    // We must have registered a valid id, else the request made no match.
    if (!empty($domain->id())) {
      $this->setActiveDomain($domain);
    }
    // Fallback to default domain if no match.
    elseif ($domain = $this->domainLoader->loadDefaultDomain()) {
      $this->moduleHandler->alter('domain_request', $domain);
      $domain->setMatchType(self::DOMAIN_MATCH_NONE);
      if (!empty($domain->id())) {
        $this->setActiveDomain($domain);
      }
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
    if ($reset || !isset($this->domain)) {
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
    $hostname = !empty($httpHost) ? $httpHost : 'localhost';
    return $this->domainLoader->prepareHostname($hostname);
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
