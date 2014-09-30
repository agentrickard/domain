<?php

/**
 * @file
 * Definition of Drupal\domain\DomainForm.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for domain edit forms.
 */
class DomainForm extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $domain = $this->entity;
    $domains = domain_load_multiple();
    // Create defaults if this is the first domain.
    if (empty($domains)) {
      $domain->hostname = domain_hostname();
      $domain->name = \Drupal::config('system.site')->get('name');
    }
    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $domain->id(),
    );
    $form['hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->getProperty('hostname'),
      '#description' => $this->t('The canonical hostname, using the full <em>path.example.com</em> format.') . '<br />' . $this->t('Leave off the http:// and the trailing slash and do not include any paths.'),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $domain->id(),
      '#machine_name' => array(
        'source' => array('hostname'),
        'exists' => 'domain_load',
      ),
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->getProperty('name'),
      '#description' => $this->t('The human-readable name of this domain.')
    );
    $form['scheme'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Domain URL scheme'),
      '#options' => array('http' => 'http://', 'https' => 'https://'),
      '#default_value' => $domain->getProperty('scheme'),
      '#description' => $this->t('The URL scheme for accessing this domain.')
    );
    $form['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Domain status'),
      '#options' => array(1 => $this->t('Active'), 0 => $this->t('Inactive')),
      '#default_value' => (int) $domain->getProperty('status'),
      '#description' => $this->t('Must be set to "Active" for users to navigate to this domain.')
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => count(domain_load_multiple()) + 1,
      '#default_value' => $domain->getProperty('weight'),
      '#description' => $this->t('The sort order for this record. Lower values display first.'),
    );
    $form['is_default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Default domain'),
      '#default_value' => $domain->isDefault(),
      '#description' => $this->t('If a URL request fails to match a domain record, the settings for this domain will be used.'),
    );
    $required = domain_required_fields();
    foreach ($form as $key => $element) {
      if (in_array($key, $required)) {
        $form[$key]['#required'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::validate().
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $validator = \Drupal::service('domain.validator');
    $errors = $validator->validate($entity);
    if (!empty($errors)) {
      form_set_error('hostname', $errors);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $domain = $this->entity;
    if ($domain->isNew()) {
      drupal_set_message($this->t('Domain record created.'));
    }
    else {
      drupal_set_message($this->t('Domain record updated.'));
    }
    $domain->save();
    $form_state->setRedirect('domain.admin');
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::delete().
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $domain = $this->entity;
    $domain->delete();
    $form_state->setRedirect('domain.admin');
  }
}
