<?php
namespace Drupal\domain_access\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\domain_access\DomainAccessManagerInterface;

class DomainAccessViewsAccess implements AccessCheckInterface {

  /**
   * The key used by the routing requirement.
   *
   * @var string
   */
  protected $requirementsKey = '_domain_access_views';

  /**
   * Sets the permission to use when checking access.
   */
  protected $permission = 'publish to any assigned domain';

  /**
   * Sets the permission to use when checking all access.
   */
  protected $allPermission = 'publish to any domain';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Domain storage handler.
   *
   * @var \Drupal\domain\DomainStorageInterface $domainStorage
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
   * @var \Drupal\domain_access\DomainAccessManagerInterface $manager
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
  public function access(Route $route, AccountInterface $account) {
    // Users with this permission can see any domain content lists, and it is
    // required to view all affiliates.
    if ($account->hasPermission($this->allPermission)) {
      return AccessResult::allowed();
    }

    // @TODO: This is not pulling the route argument. We need the actual value.
    $id = $route->getRequirement($this->requirementsKey);
    $domain = $this->domainStorage->load($id);

    // Domain found, check user permissions.
    if (!empty($domain)) {
      if ($this->allowAccess($account, $domain, $this->permission)) {
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

  /**
   * Checks that a user can access the internal page for a domain list.
   *
   * @param AccountInterface $account
   *   The user account.
   * @param DomainInterface $domain
   *   The domain being checked.
   * @param string $permission
   *   The relevant permission to check.
   *
   * @return bool
   *   Returns TRUE if the user can access the domain list page.
   */
  protected function allowAccess(AccountInterface $account, DomainInterface $domain, $permission) {
    $user = $this->userStorage->load($account->id());
    $allowed = $this->manager->getAccessValues($user);
    if ($account->hasPermission($permission) && isset($allowed[$domain->id()])) {
      return TRUE;
    }
    return FALSE;
  }

}
