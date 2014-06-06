<?php

/**
 * @file
 * Contains \Drupal\domain\Form\DomainDeleteForm.
 */

namespace Drupal\domain\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a domain record.
 */
class DomainDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'domain.admin',
    );
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Domain %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('domain', 'Domain %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route']['route_name'] = 'domain.admin';
  }

}
