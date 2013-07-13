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
 *   settings = {
 *     "pattern" = '',
 *     "redirect" = FALSE
 *   },
 *   multiple_values = TRUE
 * )
 */
class DomainAliasWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * Overrides \Drupal\file\Plugin\field\widget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
  protected function formMultipleElements(EntityInterface $entity, array $items, $langcode, array &$form, array &$form_state) {
    $elements = parent::formMultipleElements($entity, $items, $langcode, $form, $form_state);
    return $elements;
  }
   */

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
#    debug($this);
    $element = parent::formElement($items, $delta, $element, $langcode, $form, $form_state);
    $element['domain_alias'] = array(
      '#type' => 'fieldset',
      '#title' => t('Aliases'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $element['domain_alias']['pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Pattern'),
      '#default_value' => NULL,
      '#description' => t('The alias pattern to match. Use * to wildcard match multiple characters and ? to wildcard match one character.'),
    );

    $element['domain_alias']['redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect'),
      '#description' => t('When requested, redirect to the parent domain.'),
      '#default_value' => FALSE,
    );

    return array('value' => $element);
  }

}
