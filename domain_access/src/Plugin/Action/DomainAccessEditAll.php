<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Assigns a user to all affiliates.
 *
 * @Action(
 *   id = "domain_access_edit_all_action",
 *   label = @Translation("Assign editors to all affiliates"),
 *   type = "user"
 * )
 */
class DomainAccessEditAll extends DomainAccessActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set(DOMAIN_ACCESS_ALL_FIELD, 1);
    $entity->save();
  }

}
