<?php

namespace Drupal\domain;

/**
 * Class TwigExtension
 *
 * @package Drupal\domain
 */
class TwigExtension extends \Twig_Extension {

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * TwigExtension constructor.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   Domain negotiator to work out the current domain.
   */
  public function __construct(DomainNegotiatorInterface $domainNegotiator) {
    $this->domainNegotiator = $domainNegotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobals() {
    if ($domain = $this->domainNegotiator->getActiveDomain()) {
      return [
        'domain_active' => $domain,
        'domain_active_id' => $domain->id(),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    new \Twig_SimpleFunction('is_domain', [$this, 'isDomain']);
  }

  /**
   * Check of the current domain id.
   *
   * @param $domain_id
   *   Id of the domain to check.
   *
   * @return bool
   *   if the id passed in a the current domain.
   */
  public function isDomain($domain_id) {
    return $this->domainNegotiator->getActiveId() == $domain_id;
  }
}