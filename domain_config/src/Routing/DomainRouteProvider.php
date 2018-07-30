<?php

namespace Drupal\domain_config\Routing;

use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom router.route_provider service to make it domain context sensitive.
 *
 * The default behaviour is to cache routes by path and query parameters only,
 * for multiple domains this can make the home page of domain 1 be served from
 * cache as the home page of domain 2.
 */
class DomainRouteProvider extends RouteProvider {

  /**
   * Returns the cache ID for the route collection cache.
   *
   * We are overriding the cache id by inserting the host to the cid.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @see \Drupal\Core\Routing\RouteProvider::getRouteCollectionCacheId()
   *
   * @return string
   *   The cache ID.
   */
  protected function getRouteCollectionCacheId(Request $request) {
    // Include the current language code in the cache identifier as
    // the language information can be elsewhere than in the path, for example
    // based on the domain.
    $language_part = $this->getCurrentLanguageCacheIdPart();
    return 'route:' . $request->getHost() . ':' . $language_part . ':' . $request->getPathInfo() . ':' . $request->getQueryString();
  }

}
