<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Assigns a user to all affiliates.
 *
 * @Action(
 *   id = "domain_access_edit_all_action",
 *   label = @Translation("Assign editors to all affiliates"),
 *   type = "user"
 * )
 */
class DomainAccessEditAll extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return [DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD => 1];
  }

}
