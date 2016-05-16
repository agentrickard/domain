<?php

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Sets the domain status property to 1.
 *
 * @Action(
 *   id = "domain_enable_action",
 *   label = @Translation("Enable domain record"),
 *   type = "domain"
 * )
 */
class EnableDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceof DomainInterface) {
        $object->enable();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access_result = AccessResult::allowedIfHasPermission($account, 'administer domains');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
