<?php

namespace Drupal\domain\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Provides a context handler for the block system.
 */
class CurrentDomainContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The Domain negotiator.
   *
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
    $context = NULL;
    // Load the current domain.
    $current_domain = $this->negotiator->getActiveDomain();
    // Set the context, if we have a domain.
    if (!empty($current_domain) && !empty($current_domain->id())) {
      $context = EntityContext::fromEntity($current_domain, $this->t('Active domain'));
      // Allow caching.
      $cacheability = new CacheableMetadata();
      $cacheability->setCacheContexts(['url.site']);
      $context->addCacheableDependency($cacheability);
    }

    // Prepare the result.
    $result = [
      'domain' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    // See https://www.drupal.org/project/domain/issues/3201514
    if ($this->negotiator->getActiveDomain()) {
      return $this->getRuntimeContexts([]);
    }
    return [];
  }

}
