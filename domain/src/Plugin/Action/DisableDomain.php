<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Action\DisableDomain.
 */

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Sets the domain status property to 0.
 *
 * @Action(
 *   id = "domain_disable_action",
 *   label = @Translation("Disable domain record"),
 *   type = "domain"
 * )
 */
class DisableDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceOf DomainInterface) {
        $object->disable();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access_result = AccessResult::allowedIfHasPermission($account, 'administer domains');
  }

}
