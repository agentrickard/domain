<?php

/**
 * @file
 * Defines \Drupal\domain\Access\DomainAccessCheck.
 */

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class DomainAccessCheck implements AccessCheckInterface {

  /**
   * @var \Drupal\domain\DomainResolverInterface
   */
  protected $domainResolver;

  public function __construct(DomainResolverInterface $resolver) {
    $this->domainResolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $this->checkPath($route->getPath());
  }

  public function checkPath($path) {
    $list = explode('/', $path);
    if (current($list) == 'user') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $domain = $this->domainResolver->resolveActiveDomain();
    // Is the domain allowed?
    if (empty($domain)) {
      return AccessResult::allowed();
    }
    if ($domain->isEnabled()) {
      return AccessResult::allowed();
    }
    // @todo: how to issue a redirect from here.
    else {
      $permissions = array('administer domains', 'access inactive domains');
      $operator = 'OR';
      return AccessResult::allowedIfHasPermissions($account, $permissions, $operator);
    }
  }

}
