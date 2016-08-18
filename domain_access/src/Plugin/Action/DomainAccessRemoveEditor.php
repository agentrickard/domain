<?php

namespace Drupal\domain_access\Plugin\Action;

/**
 * Removes an editor from a domain.
 *
 * @Action(
 *   id = "domain_access_remove_editor_action",
 *   label = @Translation("Remove domain from editors"),
 *   type = "user"
 * )
 */
class DomainAccessRemoveEditor extends DomainAccessActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $id = $this->configuration['domain_id'];
    $user_domains = \Drupal::service('domain_access.manager')->getAccessValues($entity);

    // Skip adding the role to the user if they already have it.
    if ($entity !== FALSE && isset($user_domains[$id])) {
      unset($user_domains[$id]);
      $entity->set(DOMAIN_ACCESS_FIELD, array_keys($user_domains));
      $entity->save();
    }
  }

}
