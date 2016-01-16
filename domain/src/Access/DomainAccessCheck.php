<?php

/**
 * @file
 * Defines \Drupal\domain\Access\DomainAccessCheck.
 */

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a global access check to ensure inactive domains are restricted.
 */
class DomainAccessCheck implements AccessCheckInterface {

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs the object.
   *
   * @param DomainNegotiatorInterface $negotiator
   *   The domain negotiation service.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->domainNegotiator = $negotiator;
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
    $list = explode('/', $path);
    // @TODO: This list may need to be configurable.
    if (current($list) == 'user') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
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
