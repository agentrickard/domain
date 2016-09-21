<?php

namespace Drupal\domain;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * User interface for the domain overview screen.
 */
class DomainListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'domains';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $destination = \Drupal::destination()->getAsArray();
    $default = $entity->isDefault();
    $id = $entity->id();

    // @TODO: permission checks.
    if ($entity->status() && !$default) {
      $operations['disable'] = array(
        'title' => $this->t('Disable'),
        'url' => Url::fromRoute('domain.inline_action', array('op' => 'disable', 'domain' => $id)),
        'weight' => 50,
      );
    }
    elseif (!$default) {
      $operations['enable'] = array(
        'title' => $this->t('Enable'),
        'url' => Url::fromRoute('domain.inline_action', array('op' => 'enable', 'domain' => $id)),
        'weight' => 40,
      );
    }
    if (!$default) {
      $operations['default'] = array(
        'title' => $this->t('Make default'),
        'url' => Url::fromRoute('domain.inline_action', array('op' => 'default', 'domain' => $id)),
        'weight' => 30,
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.domain.delete_form', array('domain' => $id)),
        'weight' => 20,
      );
    }
    // @TODO: inject this service?
    $operations += \Drupal::moduleHandler()->invokeAll('domain_operations', array($entity));
    foreach ($operations as $key => $value) {
      if (isset($value['query']['token'])) {
        $operations[$key]['query'] += $destination;
      }
    }
    /** @var DomainInterface $default */
    $default = \Drupal::service('domain.loader')->loadDefaultDomain();

    // Deleting the site default domain is not allowed.
    if ($id == $default->id()) {
      unset($operations['delete']);
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['hostname'] = $this->t('Hostname');
    $header['status'] = $this->t('Status');
    $header['is_default'] = $this->t('Default');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['hostname'] = array('#markup' => $entity->getLink());
    if ($entity->isActive()) {
      $row['hostname']['#prefix'] = '<strong>';
      $row['hostname']['#suffix'] = '</strong>';
    }
    $row['status'] = array('#markup' => $entity->status() ? $this->t('Active') : $this->t('Inactive'));
    $row['is_default'] = array('#markup' => ($entity->isDefault() ? $this->t('Yes') : $this->t('No')));
    $row += parent::buildRow($entity);
    $row['weight']['#delta'] = count(\Drupal::service('domain.loader')->loadMultiple()) + 1;
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#domains'] = $this->entities;
    $form['actions']['submit']['#value'] = $this->t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('Configuration saved.'));
  }

}
