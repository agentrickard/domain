<?php

/**
 * @file
 * Definition of Drupal\domain\DomainFormController.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base form controller for domain edit forms.
 */
class DomainFormController extends ContentEntityFormController {
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $domain = $this->entity;
    // If creating a new domain, set sensible defaults.
    if (empty($domain->domain_id)) {
      $domain = domain_create();
    }
    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $domain->domain_id,
    );
    $form['hostname'] = array(
      '#type' => 'textfield',
      '#title' => t('Hostname'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->hostname,
      '#description' => t('The canonical hostname, using the full <em>path.example.com</em> format.') . '<br />' . t('Leave off the http:// and the trailing slash and do not include any paths.'),
    );
    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'source' => array('hostname'),
        'exists' => 'domain_machine_name_load',
      ),
      '#default_value' => $domain->machine_name,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->name,
      '#description' => t('The human-readable name of this domain.')
    );
    $form['scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Domain URL scheme'),
      '#options' => array('http' => 'http://', 'https' => 'https://'),
      '#default_value' => $domain->scheme,
      '#description' => t('The URL scheme for accessing this domain.')
    );
    $form['status'] = array(
      '#type' => 'radios',
      '#title' => t('Domain status'),
      '#options' => array(1 => t('Active'), 0 => t('Inactive')),
      '#default_value' => $domain->status,
      '#description' => t('Must be set to "Active" for users to navigate to this domain.')
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#delta' => count(domain_load_multiple()) + 1,
      '#default_value' => $domain->weight,
      '#description' => t('The sort order for this record. Lower values display first.'),
    );
    $form['is_default'] = array(
      '#type' => 'checkbox',
      '#title' => t('Default domain'),
      '#default_value' => $domain->is_default,
      '#description' => t('If a URL request fails to match a domain record, the settings for this domain will be used.'),
    );
    $required = domain_required_fields();
    foreach ($form as $key => $element) {
      if (in_array($key, $required)) {
        $form[$key]['#required'] = TRUE;
      }
    }
    return parent::form($form, $form_state, $domain);
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
    $domain = $this->getEntity($form_state);
    $new = is_null($domain->domain_id);
    $success = $domain->save();
    if ($new) {
      drupal_set_message(t('Domain record created.'));
    }
    else {
      drupal_set_message(t('Domain record updated.'));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $domain = $this->getEntity($form_state);
    $domain->delete();
    $form_state['redirect'] = 'admin/structure/domain';
  }
}
