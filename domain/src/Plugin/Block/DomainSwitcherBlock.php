<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainSwitcherBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\domain\Plugin\Block\DomainBlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block that links to all domains.
 *
 * @Block(
 *   id = "domain_switcher_block",
 *   admin_label = @Translation("Domain switcher")
 * )
 */
class DomainSwitcherBlock extends DomainBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermissions($account, array('administer domains', 'use domain switcher block'), 'OR');
  }

  /**
   * Build the output.
   *
   * @TODO: abstract or theme this function?
   */
  public function build() {
    $active_domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $items = array();
    foreach (\Drupal::service('domain.loader')->loadMultipleSorted() as $domain) {
      $string = $domain->getLink();
      if (!$domain->status()) {
        $string .= '*';
      }
      if ($domain->id() == $active_domain->id()) {
        $string = '<em>' . $string . '</em>';
      }
      $items[] = array('#markup' => $string);
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
  }

}
