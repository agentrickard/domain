<?php

namespace Drupal\domain_source;

use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
class DomainSourceFieldManager extends DomainFieldManager {

  /**
   * Finds options not accessible to the current user.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $field
   *   The field element being processed.
   *
   * @TODO: Move to the manager service?
   */
  public function disallowedOptions(FormStateInterface $form_state, $field) {
    $options = [];
    $info = $form_state->getBuildInfo();
    $entity = $form_state->getFormObject()->getEntity();
    $entity_values = $entity->get(DOMAIN_SOURCE_FIELD)->offsetGet(0);
    if (isset($field['widget']['#options']) && !empty($entity_values)) {
      $value = $entity_values->getValue('target_id');
      $options = array_diff_key(array_flip($value), $field['widget']['#options']);
    }
    return array_keys($options);
  }
}
