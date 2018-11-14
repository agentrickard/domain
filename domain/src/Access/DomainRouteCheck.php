<?php

namespace Drupal\domain\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Determines access to routes based on domains.
 *
 * You can specify the '_domain' key on route requirements. If you specify a
 * single domain, users with that domain with have access. If you specify
 * multiple ones you can join them by using "+".
 *
 * This access checker is separate from the global check used by inactive
 * domains. It is expressly for use with Views and other systems that need
 * to add a domain requirement to a specific route.
 */
class DomainRouteCheck implements AccessInterface {

  /**
   * The key used by the routing requirement.
   *
   * @var string
   */
  protected $requirementsKey = '_domain';

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs the object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiation service.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->domainNegotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement($this->requirementsKey);
  }

  /**
   * Checks access to a route with a _domain requirement.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\domain\Plugin\views\access\Domain
   */
  public function access(Route $route, AccountInterface $account) {
    // Requirements just allow strings, so this might be a comma separated list.
    $string = $route->getRequirement($this->requirementsKey);
    $domain = $this->domainNegotiator->getActiveDomain();
    // Since only one domain can be active per request, we only suport OR logic.
    $allowed = array_filter(array_map('trim', explode('+', $string)));
    if (!empty($domain) && in_array($domain->id(), $allowed)) {
      return AccessResult::allowed()->addCacheContexts(['url.site']);
    }
    // If there is no allowed domain, give other access checks a chance.
    return AccessResult::neutral()->addCacheContexts(['url.site']);
  }

}
