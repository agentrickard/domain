<?php

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
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
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs the object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ConfigFactoryInterface $config_factory, PathMatcherInterface $path_matcher) {
    $this->domainNegotiator = $negotiator;
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
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
    $allowed_paths = $this->configFactory->get('domain.settings')->get('login_paths');
    return !$this->pathMatcher->matchPath($path, $allowed_paths);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $domain = $this->domainNegotiator->getActiveDomain();
    // Is the domain allowed?
    // No domain, let it pass.
    if (empty($domain)) {
      return AccessResult::allowed()->addCacheTags(['url.site']);
    }
    // Active domain, let it pass.
    if ($domain->status()) {
      return AccessResult::allowed()->addCacheTags(['url.site']);
    }
    // Inactive domain, require permissions.
    else {
      $permissions = ['administer domains', 'access inactive domains'];
      $operator = 'OR';
      return AccessResult::allowedIfHasPermissions($account, $permissions, $operator)->addCacheTags(['url.site']);
    }
  }

}
