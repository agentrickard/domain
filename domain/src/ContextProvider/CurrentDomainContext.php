<?php
/**
 * @file
 * Contains \Drupal\domain\ContextProvider\CurrentDomainContext.
 */

namespace Drupal\domain\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Provides a context handler for the block system.
 */
class CurrentDomainContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

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
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Load the current domain.
    $current_domain = $this->negotiator->getActiveDomain();
    // Set the context.
    $context = new Context(new ContextDefinition('entity:domain', $this->t('Active domain')), $current_domain);

    // Allow caching.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['url.site']);
    $context->addCacheableDependency($cacheability);

    // Prepare the result.
    $result = [
      'current_domain' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
