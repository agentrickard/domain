<?php

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a global access check to ensure inactive domains are restricted.
 */
class DomainAccessCheck implements AccessCheckInterface {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the object.
   *
   * @param DomainNegotiatorInterface $negotiator
   *   The domain negotiation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ConfigFactoryInterface $config_factory) {
    $this->domainNegotiator = $negotiator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $this->checkPath($route->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function checkPath($path) {
    $allowed_paths = $this->configFactory->get('domain.settings')->get('login_paths', '/user/login\r\n/user/password');
    if (!empty($allowed_paths)) {
      $paths = preg_split("(\r\n?|\n)", $allowed_paths);
    }
    if (!empty($paths) && in_array($path, $paths)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = $this->domainNegotiator->getActiveDomain();
    // Is the domain allowed?
    // No domain, let it pass.
    if (empty($domain)) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    // Active domain, let it pass.
    if ($domain->status()) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    // Inactive domain, require permissions.
    else {
      $permissions = array('administer domains', 'access inactive domains');
      $operator = 'OR';
      return AccessResult::allowedIfHasPermissions($account, $permissions, $operator)->setCacheMaxAge(0);
    }
  }

}
