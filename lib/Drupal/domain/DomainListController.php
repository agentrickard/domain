<?php

/**
 * @file
 * Contains \Drupal\domain\Form\DomainListController.
 */

namespace Drupal\domain;

use Drupal\Core\Config\Entity\DraggableListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * User interface for the domain overview screen.
 */
class DomainListController extends DraggableListController {

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
    $default = domain_default();

    // Edit and delete path for Domains entities have a different pattern
    // than other config entities.
    $path = 'admin/config/regional/domain';
    if (isset($operations['edit'])) {
      $operations['edit']['href'] = $path . '/edit/' . $entity->id();
    }
    if (isset($operations['delete'])) {
      $operations['delete']['href'] = $path . '/delete/' . $entity->id();
    }

    // Deleting the site default domain is not allowed.
    if ($entity->id() == $default->id) {
      unset($operations['delete']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#domains'] = $this->entities;
    $form['actions']['submit']['#value'] = t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    // Kill the static cache in domain_list().
    drupal_static_reset('domain_list');

    // Update weight of locked system domains.
    domain_update_locked_weights();

    drupal_set_message(t('Configuration saved.'));
  }

}
