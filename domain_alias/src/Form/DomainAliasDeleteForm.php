<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Form\DomainAliasDeleteForm.
 */

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
    return t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('domain_alias.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message(t('DomainAlias %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('domain_alias', 'DomainAlias %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
