<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\widget\DomainWidget.
 */

namespace Drupal\domain\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'domain' widget.
 *
 * @Plugin(
 *   id = "domain",
 *   module = "domain",
 *   label = @Translation("Domain reference"),
 *   field_types = {
 *     "domain"
 *   },
 *   settings = {
 *     "all_affiliates" = TRUE
 *   },
 *   multiple_values = TRUE
 * )
 */
class DomainWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['all_affiliates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable selection of all affiliates'),
      '#default_value' => $this->getSetting('all_affiliates'),
      '#description' => t('Sending to all affiliates allows an entity to be related to all domains.'),
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $field = $this->field;
    $instance = $this->instance;

    $options = array();

    // Flag to filter the available domain options.
    // See domain_options_list().
    $filter = TRUE;

    // The element functions differently on the field configuration form.
    if ($form_state['build_info']['form_id'] == 'field_ui_field_edit_form') {
      $options[DOMAIN_ALL_AFFILIATES] = t('Send to all affiliates');
      $options[DOMAIN_CURRENT_DOMAIN] = t('Select current domain');
      // Do not filter default domains.
      $filter = FALSE;
      $default_value = isset($items[$delta]) ? $items[$delta] : array();
    }
    // If on an entity form, then read the proper values.
    elseif (isset($element['#entity_type'])) {
      $default_value = array();
      // If this key exists, we are creating a new entity. Read the defaults.
      if (isset($items[$delta]) && array_key_exists(DOMAIN_CURRENT_DOMAIN, $items[$delta])) {
        $default_value = isset($items[$delta]) ? $items[$delta] : array();
        if (!empty($items[$delta][DOMAIN_CURRENT_DOMAIN])) {
          $domain = domain_get_domain();
          $default_value[] = $domain->machine_name;
        }
      }
      // We are loading entity data. Read it now.
      else {
        $default_value = domain_extract_field_items($items, TRUE);
      }
      if (!empty($instance->definition['widget']['settings']['all_affiliates'])) {
        $options[DOMAIN_ALL_AFFILIATES] = t('Send to all affiliates');
      }
      else {
        $default_value[DOMAIN_ALL_AFFILIATES] = 0;
      }
    }

    $options += domain_options_list($filter);

    $element += array(
      '#type' => 'checkboxes',
      '#default_value' => $default_value,
      '#options' => $options,
    );

    return array('value' => $element);
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::errorElement().
   */
  public function errorElement(array $element, array $error, array $form, array &$form_state) {
    return $element['value'];
  }


}
