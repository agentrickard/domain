<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Controller\DomainAliasController.
 */

namespace Drupal\domain_alias\Controller;

use Drupal\domain\DomainInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Domain Alias module routes.
 */
class DomainAliasController implements ContainerInjectionInterface {

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
  public function __construct(EntityStorageControllerInterface $entity_storage, EntityManagerInterface $entity_manager) {
    $this->entityStorage = $entity_storage;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('domain_alias'),
      $container->get('entity.manager')
    );
  }

  /**
   * Provides the domain alias submission form.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   An domain record entity.
   *
   * @return array
   *   Returns the domain alias submission form.
   */
  public function addAlias(DomainInterface $domain) {
    // The entire purpose of this controller is to add the values from
    // the parent domain entity.
    $values['domain_id'] = $domain->id();
    $alias = entity_create('domain_alias', $values);
    return $this->entityManager->getForm($alias);
  }
}
