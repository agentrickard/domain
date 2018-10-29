<?php

namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain_config_ui\Controller\DomainConfigUIController;

/**
 * Class DeleteForm.
 */
class DeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_config_ui_delete';
  }

  /**
   * Build configuration form with metadata and values.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = NULL) {
    $elements = DomainConfigUIController::deriveElements($config_name);
    $form['help'] = [
      '#markup' => $this->t('Are you sure you want to delete the configuration override: %config_name?', ['%config_name' => $config_name]),
    ];
    $form['review'] = [
      '#type' => 'details',
      '#title' => $this->t('Review settings'),
      '#open' => FALSE,
    ];
    $form['review']['text'] = [
      '#markup' => 'test',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete configuration'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => new Url('domain_config_ui.list'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage($this->t('Domain %label has been deleted.', array('%label' => $this->entity->label())));
    \Drupal::logger('domain')->notice('Domain %label has been deleted.', array('%label' => $this->entity->label()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
