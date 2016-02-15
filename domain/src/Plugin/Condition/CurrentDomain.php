<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Condition\CurrentDomain.
 */

namespace Drupal\domain\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a 'Current domain' condition.
 *
 * @Condition(
 *   id = "current_domain",
 *   label = @Translation("Current domain"),
 *   context = {
 *     "domain" = @ContextDefinition("entity:domain", label = @Translation("Domain"))
 *   }
 * )
 *
 */
class CurrentDomain extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['current_domain'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the current domain is one of'),
      '#default_value' => $this->configuration['current_domain'],
      '#options' => array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', \Drupal::service('domain.loader')->loadOptionsList()),
      '#description' => $this->t('If you select no domains, the condition will evaluate to TRUE for all requests.'),
      '#attached' => array(
        'library' => array(
          'domain/drupal.domain',
        ),
      ),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'current_domain' => array(),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['current_domain'] = array_filter($form_state->getValue('current_domain'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the domain labels. They will be sanitized below.
    $domains = array_intersect_key(\Drupal::service('domain.loader')->loadOptionsList(), $this->configuration['current_domain']);
    if (count($domains) > 1) {
      $domains = implode(', ', $domains);
    }
    else {
      $domains = reset($domains);
    }
    if ($this->isNegated()) {
      return $this->t('Current domain is not @domains', array('@domains' => $domains));
    }
    else {
      return $this->t('Current domain is @domains', array('@domains' => $domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $domains = $this->configuration['current_domain'];
    if (empty($domains) && !$this->isNegated()) {
      return TRUE;
    }
    $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
    // NOTE: The block system handles negation for us.
    return (bool) in_array($current_domain_id, $domains);
  }

}
