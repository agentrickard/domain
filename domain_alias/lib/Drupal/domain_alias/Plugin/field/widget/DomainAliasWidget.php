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
 *     "pattern" = "",
 *     "redirect" = FALSE
 *   }
 * )
 */
class DomainAliasWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['placeholder_pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Pattern'),
      '#default_value' => $this->getSetting('placeholder_pattern'),
      '#description' => t('The alias pattern to match. Use * to wildcard match multiple characters and ? to wildcard match one character.'),
    );

    $element['placeholder_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect'),
      '#default_value' => $this->getSetting('placeholder_redirect'),
      '#description' => t('When requested, redirect to the parent domain.'),
      '#default_value' => FALSE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $placeholder_pattern = $this->getSetting('placeholder_pattern');
    $placeholder_redirect = $this->getSetting('placeholder_redirect');
    if (empty($placeholder_pattern) && empty($placeholder_redirect)) {
      $summary[] = t('No placeholders');
    }
    else {
      if (!empty($placeholder_pattern)) {
        $summary[] = t('Pattern placeholder: @placeholder_pattern', array('@placeholder_pattern' => $placeholder_pattern));
      }
      if (!empty($placeholder_redirect)) {
        $summary[] = t('URL placeholder: @placeholder_redirect', array('@placeholder_redirect' => $placeholder_redirect));
      }
    }

    return $summary;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {

    $element['pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Pattern'),
      '#placeholder' => $this->getSetting('placeholder_pattern'),
      '#default_value' => isset($items[$delta]['pattern']) ? $items[$delta]['pattern'] : NULL,
      '#description' => t('The alias pattern to match. Use * to wildcard match multiple characters and ? to wildcard match one character.'),
      '#element_validate' => array(array($this, 'validatePattern')),
    );

    $element['redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect'),
      '#placeholder' => $this->getSetting('placeholder_redirect'),
      '#default_value' => isset($items[$delta]['redirect']) ? $items[$delta]['redirect'] : FALSE,
      '#description' => t('When requested, redirect to the parent domain.'),
    );

    return $element;
  }

  /**
   * Form element validation handler.
   */
  function validatePattern(&$element, &$form_state, $form) {
    static $values;
    if (!isset($values)) {
      $values = $form_state['values'];
    }
    $value = $element['#value'];
  }

}
