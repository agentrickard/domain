<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainSwitcherBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block that links to all domains.
 *
 * @Block(
 *   id = "domain_switcher_block",
 *   admin_label = @Translation("Domain switcher")
 * )
 */
class DomainSwitcherBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return user_access('administer domains');
  }

  /**
   * Build the output.
   *
   * @TODO: abstract or theme this function?
   */
  public function build() {
    $active_domain = domain_get_domain();
    $items = array();
    foreach (domain_load_multiple() as $domain) {
      $string = l($domain->name, $domain->url, array('external' => TRUE));
      if (!$domain->status) {
        $string .= '*';
      }
      if ($domain->machine_name == $active_domain->machine_name) {
        $string = '<em>' . $string . '</em>';
      }

      $items[] = $string;
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
  }

}
