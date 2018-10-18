<?php

namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DeleteForm.
 */
class DeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this item?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('domain_config_ui.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

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
    $form = [
      '#markup' => $config_name,
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
