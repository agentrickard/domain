<?php

namespace Drupal\domain_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DomainAccessSettingsForm.
 *
 * @package Drupal\domain_access\Form
 */
class DomainAccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_access.settings');
    $form['node_advanced_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Move Domain Access fields to advanced node settings.'),
      '#default_value' => $config->get('node_advanced_tab'),
      '#description' => $this->t('When checked the Domain Access fields will be shown as a tab in the advanced settings on node edit form. However, if you have placed the fields in a field group already, they will not be moved.'),
    ];
    $form['node_advanced_tab_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open the Domain Access details.'),
      '#description' => $this->t('Set the details tab to be open by default.'),
      '#default_value' => $config->get('node_advanced_tab_open'),
      '#states' => [
        'visible' => [
          ':input[name="node_advanced_tab"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('domain_access.settings')
      ->set('node_advanced_tab', (bool) $form_state->getValue('node_advanced_tab'))
      ->set('node_advanced_tab_open', (bool) $form_state->getValue('node_advanced_tab_open'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
