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
use Symfony\Component\Routing\Route;

class DomainAccessCheck implements AccessCheckInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\domain\DomainResolverInterface
   */
  protected $domainResolver;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  public function __construct(DomainResolverInterface $resolver, AccountInterface $account, ModuleHandlerInterface $module_handler) {
    $this->domainResolver = $resolver;
    $this->account = $account;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    // @TODO: Can we filter this at all?
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route) {
    $domain = $this->domainResolver->resolveActiveDomain();
    // Is the domain allowed?
    if (!$domain) {
      return AccessResult::neutral();
    }
    if ($domain->isEnabled()) {
      return AccessResult::allowed();
    }
    // @todo: how to issue a redirect from here.
    else {
      $permissions = array('administer domains', 'access inactive domains');
      $operator = 'OR';
      return AccessResult::allowedIfHasPermissions($this->account, $permissions, $operator);
    }
  }

}
