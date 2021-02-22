<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Removes a node from a domain.
 *
 * @Action(
 *   id = "domain_access_remove_action",
 *   label = @Translation("Remove domain from content"),
 *   type = "node"
 * )
 */
class DomainAccessRemove extends DomainAccessActionBase {

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
        if (isset($existing_values[$domain_id])) {
          $save = TRUE;
          unset($values[$domain_id]);
        }
      }
    }
    if ($save) {
      $entity->set(DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD, array_keys($values));
      $entity->save();
    }
  }

}
