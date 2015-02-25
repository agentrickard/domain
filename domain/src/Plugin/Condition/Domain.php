<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Condition\Domain.
 */

namespace Drupal\domain\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a 'Domain' condition.
 *
 * @Condition(
 *   id = "domain",
 *   label = @Translation("Domain")
 * )
 *
 */
class Domain extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = array();

    foreach (domain_load_and_sort() as $key => $value) {
      $options[$key] = $value->get('name');
    }

    $form['domains'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Domains'),
      '#options' => $options,
      '#default_value' => $this->configuration['domains'],
      '#description' => $this->t('Select domains for this block to be shown. If no are selected, this block will be visible on all domains.'),
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['domains'] = array_filter($form_state->getValue('domains'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {

    $domains = $this->configuration['domains'];

    if (count($domains) > 1) {
      $domains = implode(', ', $domains);
    }
    else {
      $domains = reset($domains);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('Visible on all domains extept: @domains', array('@domains' => $domains));
    }
    else {
      return $this->t('Visible on this domains: @domains', array('@domains' => $domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return in_array(domain_get_domain()->get('id'), $this->configuration['domains']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('domains' => array()) + parent::defaultConfiguration();
  }

}
