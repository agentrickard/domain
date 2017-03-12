<?php

namespace Drupal\domain_alias;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasForm extends EntityForm {

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validator = \Drupal::service('domain_alias.validator');
    $errors = $validator->validate($this->entity);
    if (!empty($errors)) {
      $form_state->setErrorByName('pattern', $errors);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;
    $edit_link = $alias->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($alias->save() == SAVED_NEW) {
      drupal_set_message($this->t('Created new domain alias %name.', array('%name' => $alias->label())));
      $this->logger('domain_alias')->notice('Created new domain alias %name.', array('%name' => $alias->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Updated domain alias %name.'), array('%name' => $alias->label()));
      $this->logger('domain_alias')->notice('Updated domain alias %name.', array('%name' => $alias->label(), 'link' => $edit_link));
    }
    $form_state->setRedirect('domain_alias.admin', array('domain' => $alias->getDomainId()));
  }

}
