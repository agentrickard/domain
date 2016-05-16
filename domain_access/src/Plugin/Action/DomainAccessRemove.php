<?php

namespace Drupal\domain_access\Plugin\Action;


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
    $ids = $this->configuration['domain_id'];
    $save = FALSE;
    $node_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);
    // Remove domain assignment if present.
    foreach ($ids as $id) {
      if ($entity !== FALSE && isset($node_domains[$id])) {
        unset($node_domains[$id]);
        $save = TRUE;
      }
    }
    if ($save) {
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($node_domains));
      $entity->save();
    }
  }

}
