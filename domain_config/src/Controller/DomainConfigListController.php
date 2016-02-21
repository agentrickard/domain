<?php

/**
 * @file
 * Contains \Drupal\domain_config\Controller\DomainConfigListController.
 */

namespace Drupal\domain_config\Controller;

use Drupal\domain_config\DomainConfigMapperManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the domain configuration list controller.
 */
class DomainConfigListController extends ControllerBase {

  /**
   * The mapper manager.
   *
   * @var \Drupal\domain_config\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new DomainConfigListController object.
   *
   * @param \Drupal\domain_config\DomainConfigMapperManagerInterface $mapper_manager
   *   The config mapper manager.
   */
  public function __construct(DomainConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.domain_config.mapper')
    );
  }

  /**
   * Provides the listing page for any entity type.
   *
   * @param string $mapper_id
   *   The name of the mapper.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if a mapper plugin could not be instantiated from the
   *   mapper definition in the constructor.
   */
  public function listing($mapper_id) {
    $mapper_definition = $this->mapperManager->getDefinition($mapper_id);
    $mapper = $this->mapperManager->createInstance($mapper_id, $mapper_definition);
    if (!$mapper) {
      throw new NotFoundHttpException();
    }
    $entity_type = $mapper->getType();
    // If the mapper, for example the mapper for fields, has a custom list
    // controller defined, use it. Other mappers, for examples the ones for
    // node_type and block, fallback to the generic domain configuration
    // list controller.
    $build = $this->entityManager()
      ->getHandler($entity_type, 'domain_config_list')
      ->setMapperDefinition($mapper_definition)
      ->render();
    $build['#title'] = $mapper->getTypeLabel();
    return $build;
  }

}
