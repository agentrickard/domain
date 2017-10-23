<?php

namespace Drupal\domain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets a base class for injecting domain information into controllers.
 *
 * This class is useful in cases where your controller needs to respond to
 * a domain argument. Drupal doesn't do that natively, so we use this base
 * class to allow router arguments to be passed a domain object.
 *
 * @see \Drupal\domain_alias\Controller\DomainAliasController
 */
class DomainControllerBase extends ControllerBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $entityStorage;

  /**
   * Constructs a new DomainControllerBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The storage controller.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityStorageInterface $entity_storage, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityStorage = $entity_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('domain'),
      $entity_type_manager
    );
  }

}
