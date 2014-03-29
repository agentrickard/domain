<?php

/**
 * @file
 * Contains \Drupal\domain\Controller\DomainControllerBase.
 */

namespace Drupal\domain\Controller;

use Drupal\domain\DomainInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets a base class for injecting domain information into controllers.
 *
 * This class is useful in cases where your controller needs to respond to
 * a domain argument. Drupal doesn't allow that natively, so we use this base
 * class to allow router arguments to be passed a domain object.
 *
 * @see \Drupal\domain_alias\Controller\DomainAliasController
 */
class DomainControllerBase implements ContainerInjectionInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigStorageController
   */
  protected $entityStorage;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new DomainAliasController.
   *
   * @param \Drupal\Core\Config\Entity\ConfigStorageController $entity_storage
   *   The storage controller.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityStorageInterface $entity_storage, EntityManagerInterface $entity_manager) {
    $this->entityStorage = $entity_storage;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Here we return a simple manager; this may need to be overwritten for
    // class implementations.
    return new static(
      $container->get('entity.manager')->getStorageController('domain'),
      $container->get('entity.manager')
    );
  }

}
