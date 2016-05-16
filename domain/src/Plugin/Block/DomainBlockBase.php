<?php

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a common block pattern for caching.
 */
abstract class DomainBlockBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // By default, all domain blocks are per-url.
    return ['url'];
  }

}
