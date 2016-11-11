<?php

namespace Drupal\domain;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generic base class for handling hidden field options.
 *
 * Since domain options are restricted for various forms (users, nodes, source)
 * we have a base class for handling common use cases. The details of each
 * implementation are generally handled by a subclass and invoked within a
 * hook_form_alter().
 */
class DomainFieldManager {

  public function setFormOptions(array $form, FormStateInterface $form_state, $field, $class) {
    $disallowed = $class->disallowedOptions($form_state, $form[$field]);
    if (!empty($disallowed)) {
      // @TODO: Potentially show this information to users with permission.
      $form[$field_name . '_disallowed'] = array(
        '#type' => 'value',
        '#value' => $disallowed,
      );
      $form['domain_hidden_field'] = = array(
        '#type' => 'value',
        '#value' => $field_name,
      );
      // Call our submit function to merge in values.
      // Account for all the submit buttons on the node form.
      $buttons = ['submit', 'publish', 'unpublish'];
      $submit =  '\\Drupal\\domain\\DomainFieldManager::submitEntityForm';
      foreach ($buttons as $button) {
        if (isset($form['actions'][$button]['#submit'])) {
          array_unshift($form['actions'][$button]['#submit'], $submit);
        }
      }
    }
    return $form;
  }

  /**
   * Submit function for handling hidden values.
   *
   * @param $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return
   *   No return value. Hidden values are added to the field values directly.
   */
  public static function submitEntityForm(array &$form, FormStateInterface $form_state) {
    $field = $form_state->getValue('domain_hidden_field');
    $values = $form_state->getValue($field . '_disallowed');
    if (!empty($values)) {
      $info = $form_state->getBuildInfo();
      $node = $form_state->getFormObject()->getEntity();
      $entity_values = $form_state->getValue($field);
    }
    foreach ($values as $value) {
      $entity_values[]['target_id'] = $value;
    }
    $form_state->setValue($field, $entity_values);
  }

}
