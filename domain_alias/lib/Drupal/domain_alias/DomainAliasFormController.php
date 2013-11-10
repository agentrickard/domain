<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasFormController.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $alias = $this->entity;

    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $alias->domain_id,
    );
    $form['pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Pattern'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $alias->pattern,
      '#description' => t('The matching pattern for this alias.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $alias->id(),
      '#machine_name' => array(
        'source' => array('pattern'),
        'exists' => 'domain_load', // @TODO
      ),
      '#disabled' => !$alias->isNew(),
    );
    $form['redirect'] = array(
      '#type' => 'select',
      '#options' => $this->redirectOptions(),
      '#default_value' => $alias->redirect,
      '#description' => t('Redirect status'),
    );

    return parent::form($form, $form_state, $alias);
  }

  public function redirectOptions() {
    return array(
      0 => 'Do not redirect',
      301 => '301 redirect',
      302 => '302 rediret',
    );
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $errors = $entity->validate();
    if (!empty($errors)) {
      form_set_error('hostname', $errors);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $alias = $this->entity;
    if ($alias->isNew()) {
      drupal_set_message(t('Domain alias created.'));
    }
    else {
      drupal_set_message(t('Domain alias updated.'));
    }
    $alias->save();
    $form_state['redirect'] = 'admin/structure/domain/alias/' . $alias->domain_id;
  }
}
