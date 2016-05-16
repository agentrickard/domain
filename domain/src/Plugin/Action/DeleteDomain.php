<?php

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Deletes a domain record.
 *
 * @Action(
 *   id = "domain_delete_action",
 *   label = @Translation("Delete domain record"),
 *   type = "domain"
 * )
 */
class DeleteDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceof DomainInterface) {
        $object->delete();
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
