<?php

namespace Drupal\domain_alias\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a domain_alias record.
 */
class DomainAliasDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $arguments['domain'] = $this->entity->getDomainId();
    return new Url('domain_alias.admin', $arguments);
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
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('DomainAlias %label has been deleted.', array('%label' => $this->entity->label())));
    \Drupal::logger('domain_alias')->notice('DomainAlias %label has been deleted.', array('%label' => $this->entity->label()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
