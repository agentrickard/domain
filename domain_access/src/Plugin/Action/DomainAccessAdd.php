<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\Action\DomainAccessAdd.
 */

namespace Drupal\domain_access\Plugin\Action;


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
    $ids = $this->configuration['domain_id'];
    $save = FALSE;
    $node_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);
    // Add domain assignment if not present.
    foreach ($ids as $id) {
      if ($entity !== FALSE && !isset($node_domains[$id])) {
        $node_domains[$id] = $id;
        $save = TRUE;
      }
    }
    if ($save) {
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($node_domains));
      $entity->save();
    }
  }

}
