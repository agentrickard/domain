<?php

namespace Drupal\domain;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for domain edit forms.
 */
class DomainForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $this->entity;
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    // Create defaults if this is the first domain.
    if (empty($domains)) {
      $domain->addProperty('hostname', \Drupal::service('domain.creator')->createHostname());
      $domain->addProperty('name', \Drupal::config('system.site')->get('name'));
    }
    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $domain->getDomainId(),
    );
    $form['hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->getHostname(),
      '#description' => $this->t('The canonical hostname, using the full <em>subdomain.example.com</em> format. Leave off the http:// and the trailing slash and do not include any paths.<br />If this domain uses a custom http(s) port, you should specify it here, e.g.: <em>subdomain.example.com:1234</em><br />The hostname may contain only lowercase alphanumeric characters, dots, dashes, and a colon (if using alternative ports).'),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $domain->id(),
      '#machine_name' => array(
        'source' => array('hostname'),
        'exists' => '\Drupal\domain\Entity\Domain::load',
      ),
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $domain->label(),
      '#description' => $this->t('The human-readable name is shown in domain lists and may be used as the title tag.'),
    );
    // Do not use the :// suffix when storing data.
    $add_suffix = FALSE;
    $form['scheme'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Domain URL scheme'),
      '#options' => array('http' => 'http://', 'https' => 'https://'),
      '#default_value' => $domain->getScheme($add_suffix),
      '#description' => $this->t('This URL scheme will be used when writing links and redirects to this domain and its resources.'),
    );
    $form['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Domain status'),
      '#options' => array(1 => $this->t('Active'), 0 => $this->t('Inactive')),
      '#default_value' => (int) $domain->status(),
      '#description' => $this->t('"Inactive" domains are only accessible to user roles with that assigned permission.'),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => count(\Drupal::service('domain.loader')->loadMultiple()) + 1,
      '#default_value' => $domain->getWeight(),
      '#description' => $this->t('The sort order for this record. Lower values display first.'),
    );
    $form['is_default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Default domain'),
      '#default_value' => $domain->isDefault(),
      '#description' => $this->t('If a URL request fails to match a domain record, the settings for this domain will be used. Only one domain can be default.'),
    );
    $required = \Drupal::service('domain.validator')->getRequiredFields();
    foreach ($form as $key => $element) {
      if (in_array($key, $required)) {
        $form[$key]['#required'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $validator = \Drupal::service('domain.validator');
    $errors = $validator->validate($entity);
    if (!empty($errors)) {
      $form_state->setErrorByName('hostname', $errors);
    }
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $domain = $this->entity;
    $domain->delete();
    $form_state->setRedirect('domain.admin');
  }

}
