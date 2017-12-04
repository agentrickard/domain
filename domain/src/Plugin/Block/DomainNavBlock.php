<?php

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block that links to all active domains.
 *
 * @Block(
 *   id = "domain_nav_block",
 *   admin_label = @Translation("Domain navigation")
 * )
 */
class DomainNavBlock extends DomainBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = AccessResult::allowedIfHasPermissions($account, array('administer domains', 'use domain nav block'), 'OR');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Build the output.
   *
   * @TODO: abstract or theme this function?
   */
  public function build() {
    /** @var \Drupal\domain\DomainInterface $active_domain */
    $active_domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $items = [];
    $access_handler = \Drupal::service('entity_type.manager')->getAccessControlHandler('domain');
    $account = \Drupal::currentUser();
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach (\Drupal::service('entity_type.manager')->getStorage('domain')->loadMultipleSorted() as $domain) {
      $string = $domain->getLink();
      if ($domain->access('view', $account)) {
        $items[] = array('#markup' => $string);
      }
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
  }

}
