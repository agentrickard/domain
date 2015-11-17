<?php

/**
 * @file
 * Contains \Drupal\domain\Cache\Context\DomainCacheContext.
 */

namespace Drupal\domain\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\domain\ContextProvider;

/**
 * Defines the DomainCacheContext service, for "per domain" caching.
 *
 * Cache context ID: 'headers' (to vary by all headers).
 * Calculated cache context ID: 'headers:%name', e.g. 'headers:X-Something' (to
 * vary by the 'X-Something' header).
 */
class DomainCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Domain');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return Drupal::service('domain.negotiator')->getActiveDomain();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($header = NULL) {
    return new CacheableMetadata();
  }

}