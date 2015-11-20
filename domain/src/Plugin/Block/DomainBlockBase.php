<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainBlockBase.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a common block pattern for caching and access.
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
