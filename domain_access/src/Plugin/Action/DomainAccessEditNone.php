<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Removes a user from all affiliates.
 *
 * @Action(
 *   id = "domain_access_edit_none_action",
 *   label = @Translation("Remove editors from all affiliates"),
 *   type = "user"
 * )
 */
class DomainAccessEditNone extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return [DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD => 0];
  }

}
