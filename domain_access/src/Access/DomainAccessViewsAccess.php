<?php

namespace Drupal\domain_access\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Class DomainAccessViewsAccess.
 *
 * @package Drupal\domain_access\Access
 */
class DomainAccessViewsAccess implements AccessCheckInterface {

  /**
   * The key used by the routing requirement.
   *
   * @var string
   */
  protected $requirementsKey = '_domain_access_views';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Domain storage handler.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The Domain access manager.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $manager;

  /**
   * Constructs a DomainToken object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\domain_access\DomainAccessManagerInterface $manager
   *   The domain access manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DomainAccessManagerInterface $manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $this->entityTypeManager->getStorage('domain');
    $this->userStorage = $this->entityTypeManager->getStorage('user');
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, $arg_0 = NULL) {
    // Permissions are stored on the route defaults.
    $permission = $route->getDefault('domain_permission');
    $allPermission = $route->getDefault('domain_all_permission');

    // Users with this permission can see any domain content lists, and it is
    // required to view all affiliates.
    if ($account->hasPermission($allPermission)) {
      return AccessResult::allowed();
    }

    // Load the domain from the passed argument. In testing, this passed NULL
    // in some instances.
    if (!is_null($arg_0)) {
      $domain = $this->domainStorage->load($arg_0);
    }

    // Domain found, check user permissions.
    if (!empty($domain)) {
      if ($this->manager->hasDomainPermissions($account, $domain, [$permission])) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement($this->requirementsKey);
  }

}
