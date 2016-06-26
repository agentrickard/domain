<?php

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
    $id = $this->configuration['domain_id'];
    $node_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);

    // Add domain assignment if not present.
    if ($entity !== FALSE && !isset($node_domains[$id])) {
      $node_domains[$id] = $id;
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($node_domains));
      $entity->save();
    }
  }

}
