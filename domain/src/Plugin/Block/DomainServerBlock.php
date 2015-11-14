<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Block\DomainServerBlock.
 */

namespace Drupal\domain\Plugin\Block;

use Drupal\domain\Entity\Domain;
use Drupal\domain\Plugin\Block\DomainBlockBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;


/**
 * Provides a server information block for a domain request.
 *
 * @Block(
 *   id = "domain_server_block",
 *   admin_label = @Translation("Domain server information")
 * )
 */
class DomainServerBlock extends DomainBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermissions($account, array('administer domains', 'view domain information'), 'OR');
  }

  /**
   * Build the output.
   *
   * @TODO: abstract or theme this function?
   */
  public function build() {
    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    if (!$domain) {
      return array(
        '#markup' => $this->t('No domain record could be loaded.'),
      );
    }
    $header = array($this->t('Property'), $this->t('Value'));
    $rows[] = array(
      $this->t('HTTP_HOST request'),
      SafeMarkup::checkPlain($_SERVER['HTTP_HOST']),
    );
    // Check the response test.
    $domain->getResponse();
    $check = \Drupal::service('domain.loader')->loadByHostname($_SERVER['HTTP_HOST']);
    $match = $this->t('Exact match');
    if (!$check) {
      // Specific check for Domain Alias.
      if (isset($domain->alias)) {
        $match = $this->t('ALIAS: Using alias %id', array('%id' => $domain->alias));
      }
      else {
        $match = $this->t('FALSE: Using default domain.');
      }
    }
    $rows[] = array(
      $this->t('Domain match'),
      $match,
    );
    $list = $domain->toArray();
    ksort($list);
    foreach ($list as $key => $value) {
      if (is_null($value)) {
        $value = $this->t('NULL');
      }
      elseif ($value === TRUE) {
        $value = $this->t('TRUE');
      }
      elseif ($value === FALSE) {
        $value = $this->t('FALSE');
      }
      elseif ($key == 'status' || $key == 'is_default') {
        $value = empty($value) ? $this->t('FALSE') : $this->t('TRUE');
      }
      $rows[] = array(
        SafeMarkup::checkPlain($key),
        !is_array($value) ? SafeMarkup::checkPlain($value) : $this->printArray($value),
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
        $value = SafeMarkup::checkPlain($val);
      }
      else {
        $list = array();
        foreach ($val as $k => $v) {
          $list[] = $this->t('@key : @value', array('@key' => $k, '@value' => $v));
        }
        $value = implode('<br />', $list);
      }
      $items[] = $this->t('@key : @value', array('@key' => $key, '@value' => $value));
    }
    $variables['domain_server'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    return render($variables);
  }

}
