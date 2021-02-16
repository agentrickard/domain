<?php

namespace Drupal\domain_source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainElementManager;
use Drupal\domain_source\DomainSourceElementManagerInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
class DomainSourceElementManager extends DomainElementManager implements DomainSourceElementManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function disallowedOptions(FormStateInterface $form_state, array $field) {
    $options = [];
    $info = $form_state->getBuildInfo();
    $entity = $form_state->getFormObject()->getEntity();
    $entity_values = $entity->get(DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD)->offsetGet(0);
    if (isset($field['widget']['#options']) && !empty($entity_values)) {
      $value = $entity_values->getValue('target_id');
      $options = array_diff_key(array_flip($value), $field['widget']['#options']);
    }
    return array_keys($options);
  }

}
