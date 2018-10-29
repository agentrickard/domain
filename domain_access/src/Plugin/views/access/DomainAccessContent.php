<?php

namespace Drupal\domain_access\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\domain\DomainStorageInterface;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\user\UserStorageInterface;

/**
 * Access plugin that provides domain-editing access control.
 *
 * @ViewsAccess(
 *   id = "domain_access_editor",
 *   title = @Translation("Domain Access: Edit domain content"),
 *   help = @Translation("Access will be granted to domains on which the user may edit content.")
 * )
 */
class DomainAccessContent extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * Sets the permission to use when checking access.
   *
   * @var string
   */
  protected $permission = 'publish to any assigned domain';

  /**
   * Sets the permission to use when checking all access.
   *
   * @var string
   */
  protected $allPermission = 'publish to any domain';

  /**
   * Domain storage.
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
   * Domain Access manager.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $manager;

  /**
   * Constructs the access object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain storage service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage service.
   * @param \Drupal\domain_access\DomainAccessManagerInterface $manager
   *   The domain access manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainStorageInterface $domain_storage, UserStorageInterface $user_storage, DomainAccessManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainStorage = $domain_storage;
    $this->userStorage = $user_storage;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('domain_access.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Domain editor');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Users with this permission can see any domain content lists, and it is
    // required to view all affiliates.
    if ($account->hasPermission($this->allPermission)) {
      return TRUE;
    }

    // The routine below determines what domain (if any) was passed to the View.
    if (isset($this->view->element['#arguments'])) {
      foreach ($this->view->element['#arguments'] as $value) {
        if ($domain = $this->domainStorage->load($value)) {
          break;
        }
      }
    }

    // Domain found, check user permissions.
    if (!empty($domain)) {
      return $this->manager->hasDomainPermissions($account, $domain, [$this->permission]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($domains = $this->domainStorage->loadMultiple()) {
      $list = array_keys($domains);
    }
    $list[] = 'all_affiliates';
    $route->setRequirement('_domain_access_views', (string) implode('+', $list));
    $route->setDefault('domain_permission', $this->permission);
    $route->setDefault('domain_all_permission', $this->allPermission);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
