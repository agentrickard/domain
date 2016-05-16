<?php

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Sets the domain is_default property to 1.
 *
 * @Action(
 *   id = "domain_default_action",
 *   label = @Translation("Set default domain record"),
 *   type = "domain"
 * )
 */
class DefaultDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->saveDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceof DomainInterface) {
        $object->saveDefault();
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
