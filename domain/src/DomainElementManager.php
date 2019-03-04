<?php

namespace Drupal\domain;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Generic base class for handling hidden field options.
 *
 * Since domain options are restricted for various forms (users, nodes, source)
 * we have a base class for handling common use cases. The details of each
 * implementation are generally handled by a subclass and invoked within a
 * hook_form_alter().
 *
 * This class has some similarities to DomainAccessManager, but only cares
 * about form handling. It can be used as a base class by other modules that
 * show/hide domain options. See the DomainSourceElementManager for a
 * non-default implementation.
 */
class DomainElementManager implements DomainElementManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Constructs a DomainElementManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $entity_type_manager->getStorage('domain');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormOptions(array $form, FormStateInterface $form_state, $field_name, $hide_on_disallow = FALSE) {
    // There are cases, such as Entity Browser, where the form is partially
    // invoked, but without our fields.
    if (!isset($form[$field_name])) {
      return $form;
    }
    $fields = $this->fieldList($field_name);
    $empty = FALSE;
    $disallowed = $this->disallowedOptions($form_state, $form[$field_name]);
    if (empty($form[$field_name]['widget']['#options']) ||
         (count($form[$field_name]['widget']['#options']) == 1 &&
          isset($form[$field_name]['widget']['#options']['_none'])
         )
       ) {
      $empty = TRUE;
    }

    // If the domain form element is set as a group, and the field is not
    // assigned to another group, then move it. See
    // domain_access_form_node_form_alter().
    if (isset($form['domain']) && !isset($form[$field_name]['#group'])) {
      $form[$field_name]['#group'] = 'domain';
    }
    // If no values and we should hide the element, do so.
    if ($hide_on_disallow && $empty) {
      $form[$field_name]['#access'] = FALSE;
    }
    // Check for domains the user cannot access or the absence of any options.
    if (!empty($disallowed) || $empty) {
      // @TODO: Potentially show this information to users with permission.
      $form[$field_name . '_disallowed'] = [
        '#type' => 'value',
        '#value' => $disallowed,
      ];
      $form['domain_hidden_fields'] = [
        '#type' => 'value',
        '#value' => $fields,
      ];
      if ($hide_on_disallow || $empty) {
        $form[$field_name]['#access'] = FALSE;
      }
      elseif (!empty($disallowed)) {
        $form[$field_name]['widget']['#description'] .= $this->listDisallowed($disallowed);
      }
      // Call our submit function to merge in values.
      // Account for all the submit buttons on the node form.
      $buttons = ['preview', 'delete'];
      $submit = $this->getSubmitHandler();
      foreach ($form['actions'] as $key => $action) {
        if (!in_array($key, $buttons) && isset($form['actions'][$key]['#submit']) && !in_array($submit, $form['actions'][$key]['#submit'])) {
          array_unshift($form['actions'][$key]['#submit'], $submit);
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function submitEntityForm(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValue('domain_hidden_fields');
    foreach ($fields as $field) {
      $entity_values = [];
      $values = $form_state->getValue($field . '_disallowed');
      if (!empty($values)) {
        $info = $form_state->getBuildInfo();
        $node = $form_state->getFormObject()->getEntity();
        $entity_values = $form_state->getValue($field);
      }
      if (is_array($values)) {
        foreach ($values as $value) {
          $entity_values[]['target_id'] = $value;
        }
      }
      else {
        $entity_values[]['target_id'] = $values;
      }
      // Prevent a fatal error caused by passing a NULL value.
      // See https://www.drupal.org/node/2841962.
      if (!empty($entity_values)) {
        $form_state->setValue($field, $entity_values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disallowedOptions(FormStateInterface $form_state, array $field) {
    $options = [];
    $info = $form_state->getBuildInfo();
    $entity = $form_state->getFormObject()->getEntity();
    $entity_values = $this->getFieldValues($entity, $field['widget']['#field_name']);
    if (isset($field['widget']['#options'])) {
      $options = array_diff_key($entity_values, $field['widget']['#options']);
    }
    return array_keys($options);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldList($field_name) {
    static $fields = [];
    $fields[] = $field_name;
    // Return only unique field names. AJAX requests can result in duplicates.
    // See https://www.drupal.org/project/domain/issues/2930934.
    return array_unique($fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValues(EntityInterface $entity, $field_name) {
    // @TODO: static cache.
    $list = [];
    // @TODO In tests, $entity is returning NULL.
    if (is_null($entity)) {
      return $list;
    }
    // Get the values of an entity.
    $values = $entity->hasField($field_name) ? $entity->get($field_name) : NULL;
    // Must be at least one item.
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          if ($domain = $this->domainStorage->load($target['target_id'])) {
            $list[$domain->id()] = $domain->getDomainId();
          }
        }
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandler() {
    return '\\Drupal\\domain\\DomainElementManager::submitEntityForm';
  }

  /**
   * Lists the disallowed domains in the user interface.
   *
   * @param array $disallowed
   *   An array of domain ids.
   *
   * @return string
   *   A string suitable for display.
   */
  public function listDisallowed(array $disallowed) {
    $domains = $this->domainStorage->loadMultiple($disallowed);
    $string = $this->t('The following domains are currently assigned and cannot be changed:');
    foreach ($domains as $domain) {
      $items[] = $domain->label();
    }
    $build = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $string .= render($build);
    return '<div class="disallowed">' . $string . '</div>';
  }

}
