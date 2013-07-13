<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Plugin\field\widget\DomainAliasWidget.
 */

namespace Drupal\domain_alias\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'domain alias' widget.
 *
 * @Plugin(
 *   id = "domain_alias",
 *   module = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   field_types = {
 *     "domain_alias"
 *   },
 *   multiple_values = TRUE
 * )
 */
class DomainAliasWidget extends WidgetBase {

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
      $filter = FALSE;
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
