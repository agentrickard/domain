<?php

namespace Drupal\domain;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the domain alias entity type.
 *
 * Note that this is not a node access check.
 */
class DomainAliasAccessControlHandler extends EntityAccessControlHandler {

 /**
  * The entity type manager
  *
  * @var \Drupal\Core\Entity\EntityTypeManagerInterface
  */
  protected $entityTypeManager;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // Check the global permission.
    kint($entity);
    if ($account->hasPermission('administer domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'create' && $account->hasPermission('create domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'update' && $account->hasPermission('edit domain aliases')) {
      return AccessResult::allowed();
    }
    if ($operation == 'delete' && $account->hasPermission('delete domain aliases')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}
