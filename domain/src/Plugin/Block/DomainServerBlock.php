<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainServerBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\Component\Utility\String;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

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
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer domains');
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
      String::checkPlain($_SERVER['HTTP_HOST']),
    );
    // Check the response test.
    $domain->checkResponse();
    $check = domain_load_hostname($_SERVER['HTTP_HOST']);
    $match = t('Exact match');
    if (!$check) {
      // Specific check for Domain Alias.
      if (isset($domain->alias)) {
        $match = t('ALIAS: Using alias %id', array('%id' => $domain->alias));
      }
      else {
        $match = t('FALSE: Using default domain.');
      }
    }
    $rows[] = array(
      t('Domain match'),
      $match,
    );
    $list = (array) $domain;
    ksort($list);
    foreach ($list as $key => $value) {
      if (is_null($value)) {
        $value = t('NULL');
      }
      elseif ($value === TRUE) {
        $value = t('TRUE');
      }
      elseif ($value === FALSE) {
        $value = t('FALSE');
      }
      elseif ($key == 'status' || $key == 'is_default') {
        $value = empty($value) ? t('FALSE') : t('TRUE');
      }
      $rows[] = array(
        String::checkPlain($key),
        !is_array($value) ? String::checkPlain($value) : $this->printArray($value),
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
        $value = String::checkPlain($val);
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
    $variables['domain_server'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    return render($variables);
  }

}
