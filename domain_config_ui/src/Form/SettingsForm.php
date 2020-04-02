<?php

namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_config_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_config_ui.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_config_ui.settings');
    $form['remember_domain'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember domain selection'),
      '#default_value' => $config->get('remember_domain'),
      '#description' => $this->t('Keeps last selected domain when loading new configuration forms.'),
    ];
    $form['pages'] = [
      '#title' => $this->t('Enabled configuration forms'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $form['pages']['path_pages'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#columns' => 40,
      '#default_value' => $config->get('path_pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. Paths must start with /admin. Wildcards (*) are not supported. An example path is /admin/appearance for the Appearance page."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path_pages = $form_state->getValue('path_pages');
    $path_pages = str_replace(["\r\n", "\n", "\r"], '|', $path_pages);
    $pages = explode("|", $path_pages);
    $exists = [];
    foreach ($pages as $page) {
      if (in_array($page, $exists)) {
        $form_state->setError($form['pages']['path_pages'], $this->t('Duplicate paths cannot be added'));
      }
      $exists[] = $page;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean session values.
    unset($_SESSION['domain_config_ui_domain']);
    unset($_SESSION['domain_config_ui_language']);
    $this->config('domain_config_ui.settings')
      ->set('remember_domain', $form_state->getValue('remember_domain'))
      ->set('path_pages', $form_state->getValue('path_pages'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
