<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Removes a node to all affiliates..
 *
 * @Action(
 *   id = "domain_access_none_action",
 *   label = @Translation("Remove from all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessNone extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return [DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD => 0];
  }

}
