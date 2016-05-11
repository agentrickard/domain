<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainSwitcherBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\Entity\Domain;

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
    $access = AccessResult::allowedIfHasPermissions($account, array('administer domains', 'use domain switcher block'), 'OR');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Build the output.
   *
   * @TODO: abstract or theme this function?
   */
  public function build() {
    /** @var Domain $active_domain */
    $active_domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $items = array();
    /** @var Domain $domain */
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
