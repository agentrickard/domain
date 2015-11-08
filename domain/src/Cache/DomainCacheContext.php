<?php

/**
 * @file
 * Contains \Drupal\domain\Cache\DomainCacheContext.
 */

namespace Drupal\domain\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Defines the DomainCacheContext service that allows caching per domain.
 *
 * Cache context ID: 'domain'.
 */
class DomainCacheContext implements CacheContextInterface {

  use StringTranslationTrait;

  protected $domain;

  /**
   * Constructs a CurrentDomainContext object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
    $this->domain = $this->negotiator->getActiveDomain();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return $this->t('Domain request');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->domain->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // Cache context is domain-sensitive.
    $tags = ['domain:' . $this->domain->id()];

    return $cacheable_metadata->setCacheTags($tags);
  }

}
