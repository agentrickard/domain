<?php

/**
 * @file
 * Contains \Drupal\domain\Cache\DomainCacheContext.
 */

namespace Drupal\domain\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Defines the DomainCacheContext service that allows caching per domain.
 *
 * Cache context ID: 'domain'.
 */
class DomainCacheContext implements CacheContextInterface {

  /**
   * Constructs a CurrentDomainContext object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
      $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Domain request');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $domain = $this->negotiator->getActiveDomain();
    return $domain->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    return $metadata;
  }

}
