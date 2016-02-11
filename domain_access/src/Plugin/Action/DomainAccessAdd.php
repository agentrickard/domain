<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\Action\DomainAccessAdd.
 */

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\Plugin\Action\DomainAccessActionBase;
use Drupal\Core\Session\AccountInterface;

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
    $id = $this->configuration['id'];
    $node_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);
    // Skip adding the role to the user if they already have it.
    if ($entity !== FALSE && !isset($node_domains[$id])) {
      $node_domains[$id] = $id;
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($new_domains));
      $entity->save();
    }
  }

}
