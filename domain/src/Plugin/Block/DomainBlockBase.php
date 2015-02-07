<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainBlockBase.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates a common block pattern for caching and access.
 */
abstract class DomainBlockBase extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($account, 'administer domains');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // By default, all domain blocks are per-url.
    return array('cache_context.url');
  }

}
