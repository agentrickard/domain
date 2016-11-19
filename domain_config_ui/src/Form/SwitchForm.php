<?php
namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SwitchForm extends FormBase {
  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'domain_config_ui_switch_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (isset($_SESSION['domain_config_ui']['config_save_mode'])) {
      $selected = $_SESSION['domain_config_ui']['config_save_mode'];
    }
    else {
      $selected = $form_state->getValue('config_save_mode');
    }
    $form['config_save_mode'] = array(
      '#type' => 'select',
      '#title' => 'Save config for:',
      '#options' => [
        'all' => 'All Domains',
        'current' => 'Current Domain',
      ],
      '#default_value' => $selected,
      '#ajax' => array(
        'callback' => 'Drupal\domain_config_ui\Form\SwitchForm::switchCallback',
      ),
    );

    // Attach CSS to position form.
    $form['#attached']['library'][] = 'domain_config_ui/drupal.domain_config_ui.admin';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form does not require submit handler.
  }

  /**
   * Callback to remember save mode.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function switchCallback(array &$form, FormStateInterface $form_state) {
    $_SESSION['domain_config_ui']['config_save_mode'] = $form_state->getValue('config_save_mode');
  }
}
