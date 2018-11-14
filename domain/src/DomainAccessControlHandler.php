<?php

namespace Drupal\domain;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access controller for the domain entity type.
 *
 * Note that this is not a node access check.
 */
class DomainAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain field element manager.
   *
   * @var \Drupal\domain\DomainElementManagerInterface
   */
  protected $domainElementManager;

  /**
   * The user storage manager.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\domain\DomainElementManagerInterface $domain_element_manager
   *   The domain field element manager.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DomainElementManagerInterface $domain_element_manager, UserStorageInterface $user_storage) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
    $this->domainElementManager = $domain_element_manager;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('domain.element_manager'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);
    // Check the global permission.
    if ($account->hasPermission('administer domains')) {
      return AccessResult::allowed();
    }
    // @TODO: This may not be relevant.
    if ($operation == 'create' && $account->hasPermission('create domains')) {
      return AccessResult::allowed();
    }
    // For view, we allow admins unless the domain is inactive.
    $is_admin = $this->isDomainAdmin($entity, $account);
    if ($operation == 'view' && ($entity->status() || $account->hasPermission('access inactive domains')) && ($is_admin || $account->hasPermission('view domain list'))) {
      return AccessResult::allowed();
    }
    // For other operations, check that the user is a domain admin.
    if ($operation == 'update' && $account->hasPermission('edit assigned domains') && $is_admin) {
      return AccessResult::allowed();
    }
    if ($operation == 'delete' && $account->hasPermission('delete assigned domains') && $is_admin) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Checks if a user can administer a specific domain.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if a user can administer a specific domain, or FALSE.
   */
  public function isDomainAdmin(EntityInterface $entity, AccountInterface $account) {
    $user = $this->userStorage->load($account->id());
    $user_domains = $this->domainElementManager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    return isset($user_domains[$entity->id()]);
  }

}
