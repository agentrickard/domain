<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Assigns a node to all affiliates.
 *
 * @Action(
 *   id = "domain_access_all_action",
 *   label = @Translation("Assign to all affiliates"),
 *   type = "node"
 * )
 */
class DomainAccessAll extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return [DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD => 1];
  }

}
