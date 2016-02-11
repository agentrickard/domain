<?php

/**
 * @file
 * Contains \Drupal\domain_access\Plugin\Action\DomainAccessRemove.
 */

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\Plugin\Action\DomainAccessActionBase;
use Drupal\Core\Session\AccountInterface;

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
    $id = $this->configuration['id'];
    $node_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);
    // Skip adding the role to the user if they already have it.
    if ($entity !== FALSE && isset($node_domains[$id])) {
      unset($node_domains[$id]);
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($new_domains));
      $entity->save();
    }
  }

}
