<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Assigns a node to a domain.
 *
 * @Action(
 *   id = "domain_access_add_action",
 *   label = @Translation("Add domain to content"),
 *   type = "node"
 * )
 */
class DomainAccessAdd extends DomainAccessActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $save = FALSE;
    if ($entity) {
      $ids = $this->configuration['domain_id'];
      $existing_values = \Drupal::service('domain_access.manager')->getAccessValues($entity);
      $values = $existing_values;
      foreach ($ids as $domain_id) {
        if (!isset($existing_values[$domain_id])) {
          $save = TRUE;
          $values[$domain_id] = $domain_id;
        }
      }
    }
    if ($save) {
      $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD, array_keys($values));
      $entity->save();
    }
  }

}
