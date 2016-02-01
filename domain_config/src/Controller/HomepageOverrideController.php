<?php

/**
 * @file
 * Contains \Drupal\domain_config\Controller\HomepageOverrideController.
 */

namespace Drupal\domain_config\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Custom handling of homepage routes.
 */
class HomepageOverrideController {

  public function homePage() {
    $provider = \Drupal::service('router.route_provider');
    $config = \Drupal::service('config.factory');
    $uri = $config->get('system.site')->get('page.front');
    $server = $_SERVER;
    $request = Request::create($uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server);
    $route = $provider->getRouteCollectionForRequest($request);
    if (!empty($route) && $object = $route->getIterator()) {
      $callable = current($object);
      if ($controller = $callable->getDefault('_controller')) {
        $path_array = explode('/', trim($uri, '/'));
        $options = $callable->getOptions();
        $response = $this->forward($controller, $request);
      }
    }
    if (!empty($response)) {
      return $response;
    }
    else {
      $response = new Response('An error has occured loading the page.');
    }
  }


  /**
   * Forwards the request to another controller.
   *
   * This code is lifted from https://github.com/symfony/symfony/blob/2.8/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
   * and then modified. If we specify the controller without proper arguments,
   * everything explodes.
   *
   * This is very close to working....
   *
   * @param string $controller The controller name (a string like BlogBundle:Post:index)
   * @param array  $path       An array of path parameters
   * @param array  $query      An array of query parameters
   *
   * @return Response A Response instance
   */
  public function forward($controller, $request) {
    $subRequest = clone $request;
    return \Drupal::service('http_kernel.basic')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
  }

}
