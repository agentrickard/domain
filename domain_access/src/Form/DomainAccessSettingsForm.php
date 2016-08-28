<?php
/**
 * @file
 * Settings form for Domain Access.
 */

namespace Drupal\domain_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $form['node_advanced_tab'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Move Domain Access fields to advanced node settings.'),
      '#default_value' => $config->get('node_advanced_tab'),
      '#description' => $this->t('When checked the Domain Access fields will be shown as a tab in the advanced settings on node edit form. However, if you have placed the fields in a field group already, they will not be moved.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('domain_access.settings')
      ->set('node_advanced_tab', $form_state->getValue('node_advanced_tab'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}
