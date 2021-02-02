<?php

namespace Drupal\domain\Routing;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom router.route_provider service to make it domain context sensitive.
 *
 * The default behaviour is to cache routes by path and query parameters only,
 * for multiple domains this can make the home page of domain 1 be served from
 * cache as the home page of domain 2.
 *
 * Originally used by Domain Config, this behavior is tested in
 * domain_config/tests/src/Functional/DomainConfigHomepageTest.php.
 *
 * We have moved the behavior to the main module to better support extension
 * modules that do not require Domain Config, such as Domain Path.
 */
class DomainRouteProvider extends RouteProvider {

  /**
   * DomainRouteProvider constructor.
   *
   * Extends the core RouteProvider. Note that the Kernel tests load a
   * different RouteProvider, which means we cannot declare a common interface
   * for the $inner_service parameter.
   *
   * @see Drupal\Core\Routing\RouteProvider::__construct()
   */
  public function __construct($inner_service, Connection $connection, StateInterface $state, CurrentPathStack $current_path, CacheBackendInterface $cache_backend, InboundPathProcessorInterface $path_processor, CacheTagsInvalidatorInterface $cache_tag_invalidator, $table = 'router', LanguageManagerInterface $language_manager = NULL) {
    $this->innerService = $inner_service;
    parent::__construct($connection, $state, $current_path, $cache_backend, $path_processor, $cache_tag_invalidator, $table, $language_manager);
  }

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
