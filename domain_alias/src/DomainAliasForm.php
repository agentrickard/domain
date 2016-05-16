<?php

namespace Drupal\domain_alias;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasForm extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;

    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $alias->getDomainId(),
    );
    $form['pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $alias->getPattern(),
      '#description' => $this->t('The matching pattern for this alias.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $alias->id(),
      '#machine_name' => array(
        'source' => array('pattern'),
        'exists' => '\Drupal\domain_alias\Entity\DomainAlias::load',
      ),
    );
    $form['redirect'] = array(
      '#type' => 'select',
      '#options' => $this->redirectOptions(),
      '#default_value' => $alias->getRedirect(),
      '#description' => $this->t('Redirect status'),
    );

    return parent::form($form, $form_state);
  }

  /**
   * Returns a list of valid redirect options for the form.
   *
   * @return array
   *   A list of valid redirect options.
   */
  public function redirectOptions() {
    return array(
      0 => $this->t('Do not redirect'),
      301 => $this->t('301 redirect: Moved Permanently'),
      302 => $this->t('302 redirect: Found'),
    );
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::validate().
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $validator = \Drupal::service('domain_alias.validator');
    $errors = $validator->validate($entity);
    if (!empty($errors)) {
      $form_state->setErrorByName('pattern', $errors);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;
    if ($alias->isNew()) {
      drupal_set_message($this->t('Domain alias created.'));
    }
    else {
      drupal_set_message($this->t('Domain alias updated.'));
    }
    $alias->save();
    $form_state->setRedirect('domain_alias.admin', array('domain' => $alias->getDomainId()));
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::delete().
   */
  public function delete(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;
    // @TODO: error handling?
    $alias->delete();

    $form_state->setRedirect('domain_alias.admin', array('domain' => $alias->getDomainId()));
  }

}
