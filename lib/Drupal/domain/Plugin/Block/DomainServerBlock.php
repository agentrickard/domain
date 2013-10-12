<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainServerBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a server information block for a domain request.
 *
 * @Block(
 *   id = "domain_server_block",
 *   admin_label = @Translation("Domain server information")
 * )
 */
class DomainServerBlock extends BlockBase {

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
    $domain = domain_get_domain();
    $header = array(t('Property'), t('Value'));
    $rows[] = array(
      t('HTTP_HOST request'),
      check_plain($_SERVER['HTTP_HOST']),
    );
    $check = domain_load_hostname($_SERVER['HTTP_HOST']);
    $match = t('TRUE');
    if (!$check) {
      // Specific check for Domain Alias.
      if (isset($domain['active_alias_id'])) {
        $match = t('ALIAS: Using alias %id', array('%id' => $domain['active_alias_id']));
      }
      else {
        $match = t('FALSE: Using default domain.');
      }
    }
    $rows[] = array(
      t('Domain match'),
      $match,
    );
    $property_definitions = $domain->getPropertyDefinitions();
    foreach ($property_definitions as $key => $val) {
      $value = $domain->{$key}->value;
      $rows[] = array(
        check_plain($key),
        !is_array($value) ? check_plain($value) : $this->printArray($value),
      );
    }
    return array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );
  }

  /**
   * Prints array data for the server block.
   *
   * @param $array
   *  An array of data. Note that we support two levels of nesting.
   *
   * @return
   *  A suitable output string.
   */
  public function printArray(array $array) {
    $items = array();
    foreach ($array as $key => $val) {
      $value = 'array';
      if (!is_array($val)) {
        $value = check_plain($val);
      }
      else {
        $list = array();
        foreach ($val as $k => $v) {
          $list[] = t('@key : @value', array('@key' => $k, '@value' => $v));
        }
        $value = implode('<br />', $list);
      }
      $items[] = t('@key : !value', array('@key' => $key, '!value' => $value));
    }
    return theme('item_list', array('items' => $items));
  }

}
