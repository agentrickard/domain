<?php

namespace Drupal\domain;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Handles requests for token creation.
 *
 * TokenAPI still uses procedural code, but we have moved it to a class for
 * easier refactoring.
 */

/**
 * Token handler for Domain.
 */
class DomainToken {

  /**
   * The Domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface $loader
   */
  protected $loader;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface $negotiator
   */
  protected $negotiator;

  /**
   * Constructs a DomainToken object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * hook_token_info().
   */
  public function getTokenInfo() {
    $info = [];

    return $info;
  }

  /**
   * hook_tokens().
   */
  public function getTokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $tokens = [];

    return $tokens;
  }


}
