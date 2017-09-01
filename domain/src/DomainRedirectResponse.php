<?php

namespace Drupal\domain;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\Core\Routing\CacheableSecuredRedirectResponse;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a redirect response which understands domain URLs are local to the install.
 *
 * This class can be used in cases where LocalRedirectResponse needs to be domain
 * sensitive. The main implementation is in DomainSourceRedirectResponseSubscriber.
 *
 * This class combines LocalAwareRedirectResponseTrait and UrlHelper methods that
 * cannot be overridden safely otherwise.
 */
class DomainRedirectResponse extends CacheableSecuredRedirectResponse {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * {@inheritdoc}
   */
  protected function isLocal($url) {
    $base_url = $this->getRequestContext()->getCompleteBaseUrl();
    return !UrlHelper::isExternal($url) || UrlHelper::externalIsLocal($url, $base_url) || $this->externalIsRegistered($url, $base_url);
  }

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    return $this->isLocal($url);
  }

  /**
   * Returns the request context.
   *
   * @return \Drupal\Core\Routing\RequestContext
   */
  protected function getRequestContext() {
    if (!isset($this->requestContext)) {
      $this->requestContext = \Drupal::service('router.request_context');
    }
    return $this->requestContext;
  }

  /**
   * Sets the request context.
   *
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   *
   * @return $this
   */
  public function setRequestContext(RequestContext $request_context) {
    $this->requestContext = $request_context;

    return $this;
  }

  /**
   * Determines if an external URL points to this domain-aware installation.
   *
   * This method replaces the logic in
   * Drupal\Component\Utility\UrlHelper::externalIsLocal(). Since that class is not
   * directly replaceable, we have to replace it.
   *
   * @param string $url
   *   A string containing an external URL, such as "http://example.com/foo".
   *
   * @return bool
   *   TRUE if the URL has the same domain and base path.
   * @param string $base_url
   *   The base URL string to check against, such as "http://example.com/"
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when $url is not fully qualified.
   */
  public static function externalIsRegistered($url, $base_url) {
    $url_parts = parse_url($url);
    $base_parts = parse_url($base_url);

    if (empty($url_parts['host'])) {
      throw new \InvalidArgumentException('A path was passed when a fully qualified domain was expected.');
    }

    // Check that the requested $url is registered.
    $negotiator = \Drupal::service('domain.negotiator');
    $registered_domain = $negotiator->isRegisteredDomain($url_parts['host']);

    if (!isset($url_parts['path']) || !isset($base_parts['path'])) {
      return $registered_domain;
    }
    else {
      // When comparing base paths, we need a trailing slash to make sure a
      // partial URL match isn't occurring. Since base_path() always returns
      // with a trailing slash, we don't need to add the trailing slash here.
      return ($registered_domain && stripos($url_parts['path'], $base_parts['path']) === 0);
    }
  }

}
