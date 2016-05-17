<?php

namespace Drupal\domain_config\Routing;

use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Cache\CacheBackendInterface;
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
   * {@inheritdoc}
   *
   * Modify the caching of the route.
   */
  public function getRouteCollectionForRequest(Request $request) {
    // Cache both the system path as well as route parameters and matching
    // routes. Here we add in the domain as well.
    $cid = 'route:' . $request->getHost() . ':' . $request->getPathInfo() . ':' . $request->getQueryString();
    if ($cached = $this->cache->get($cid)) {
      $this->currentPath->setPath($cached->data['path'], $request);
      $request->query->replace($cached->data['query']);
      return $cached->data['routes'];
    }
    else {
      // Just trim on the right side.
      $path = $request->getPathInfo();
      $path = $path === '/' ? $path : rtrim($request->getPathInfo(), '/');
      $path = $this->pathProcessor->processInbound($path, $request);
      $this->currentPath->setPath($path, $request);
      // Incoming path processors may also set query parameters.
      $query_parameters = $request->query->all();
      $routes = $this->getRoutesByPath(rtrim($path, '/'));
      $cache_value = [
        'path' => $path,
        'query' => $query_parameters,
        'routes' => $routes,
      ];
      $this->cache->set($cid, $cache_value, CacheBackendInterface::CACHE_PERMANENT, ['route_match']);
      return $routes;
    }
  }

}
