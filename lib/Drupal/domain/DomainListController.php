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
    $destination = drupal_get_destination();
    $default = $entity->is_default;
    $id = $entity->id();
    if ($entity->status && !$default) {
      $operations['disable'] = array(
        'title' => t('Disable'),
        'href' => "admin/structure/domain/disable/$id",
        'query' => array('token' => drupal_get_token()),
        'weight' => 50,
      );
    }
    elseif (!$default) {
      $operations['enable'] = array(
        'title' => t('Enable'),
        'href' => "admin/structure/domain/enable/$id",
        'query' => array('token' => drupal_get_token()),
        'weight' => 40,
      );
    }
    if (!$default) {
      $operations['default'] = array(
        'title' => t('Make default'),
        'href' => "admin/structure/domain/default/$id",
        'query' => array('token' => drupal_get_token()),
        'weight' => 30,
      );
      $operations['delete'] = array(
        'title' => t('Delete'),
        'href' => "admin/structure/domain/delete/$id",
        'query' => array(),
        'weight' => 20,
      );
    }
    // @TODO: should this be handled differently?
    $operations += \Drupal::moduleHandler()->invokeAll('domain_operations', array($entity));
    foreach ($operations as $key => $value) {
      if (isset($value['query']['token'])) {
        $operations[$key]['query'] += $destination;
      }
    }
    $default = domain_default();

    // Deleting the site default domain is not allowed.
    if ($id == $default->id) {
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
    $row += parent::buildRow($entity);
    $row['weight']['#delta'] = count(domain_load_multiple()) + 1;
    return $row;
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

    drupal_set_message(t('Configuration saved.'));
  }

}
