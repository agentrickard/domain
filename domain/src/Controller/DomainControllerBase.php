<?php

namespace Drupal\domain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainStorageInterface;

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
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DomainControllerBase.
   *
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   The storage controller.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(DomainStorageInterface $domain_storage, EntityTypeManagerInterface $entity_type_manager) {
    $this->domainStorage = $domain_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('entity_type.manager')
    );
  }

}
