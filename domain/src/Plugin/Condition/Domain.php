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
 *   label = @Translation("Domain"),
 *   context = {
 *     "domain" = @ContextDefinition("entity:domain", label = @Translation("Domain"))
 *   }
 * )
 *
 */
class Domain extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['domains'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the following domains are active'),
      '#default_value' => $this->configuration['domains'],
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
      'domains' => array(),
    ) + parent::defaultConfiguration();
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
    // Use the domain labels. They will be sanitized below.
    $domains = array_intersect_key(\Drupal::service('domain.loader')->loadOptionsList(), $this->configuration['domains']);
    if (count($domains) > 1) {
      $domains = implode(', ', $domains);
    }
    else {
      $domains = reset($domains);
    }
    if ($this->isNegated()) {
      return $this->t('Active domain is not @domains', array('@domains' => $domains));
    }
    else {
      return $this->t('Active domain is @domains', array('@domains' => $domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $domains = $this->configuration['domains'];
    if (empty($domains) && !$this->isNegated()) {
      return TRUE;
    }
    $domain = $this->getContextValue('domain');
    // NOTE: The block system handles negation for us.
    return (bool) in_array($domain->id(), $domains);
  }

}
