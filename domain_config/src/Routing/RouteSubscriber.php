<?php

/**
 * @file
 * Contains \Drupal\domain_config\Routing\RouteSubscriber.
 */

namespace Drupal\domain_config\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\domain_config\DomainConfigMapperManagerInterface;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\domain_config\DomainConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\domain_config\DomainConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(DomainConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $mappers = $this->mapperManager->getMappers($collection);

    foreach ($mappers as $mapper) {
      $collection->add($mapper->getOverviewRouteName(), $mapper->getOverviewRoute());
      $collection->add($mapper->getAddRouteName(), $mapper->getAddRoute());
      $collection->add($mapper->getEditRouteName(), $mapper->getEditRoute());
      $collection->add($mapper->getDeleteRouteName(), $mapper->getDeleteRoute());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after field_ui.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -120);
    return $events;
  }

}
