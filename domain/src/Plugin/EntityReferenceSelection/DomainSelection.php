<?php

namespace Drupal\domain\Plugin\EntityReferenceSelection;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides entity reference selections for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:domain",
 *   label = @Translation("Domain selection"),
 *   entity_types = {"domain"},
 *   group = "default",
 *   weight = 1
 * )
 */
class DomainSelection extends DefaultSelection {

  /**
   * Sets the context for the alter hook.
   *
   * @var string
   */
  protected $field_type = 'editor';

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Let administrators do anything.
    if ($this->currentUser->hasPermission('administer domains')) {
      return $query;
    }
    // Can this user access inactive domains?
    if (!$this->currentUser->hasPermission('access inactive domains')) {
      $query->condition('status', 1);
    }
    // Filter domains by the user's assignments, which are controlled by other
    // modules. Those modules must know what type of entity they are dealing
    // with, so look up the entity type and bundle.
    $info = $query->getMetaData('entity_reference_selection_handler');

    if (!empty($info->configuration['entity'])) {
      $context['entity_type'] = $info->configuration['entity']->getEntityTypeId();
      $context['bundle'] = $info->configuration['entity']->bundle();
      $context['field_type'] = $this->field_type;

      // Load the current user.
      $account = User::load($this->currentUser->id());
      // Run the alter hook.
      $this->moduleHandler->alter('domain_references', $query, $account, $context);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $selection_handler_settings = $this->configuration['handler_settings'];

    // Merge-in default values.
    $selection_handler_settings += array(
      // For the 'target_bundles' setting, a NULL value is equivalent to "allow
      // entities from any bundle to be referenced" and an empty array value is
      // equivalent to "no entities from any bundle can be referenced".
      'target_bundles' => NULL,
      'sort' => array(
        'field' => 'weight',
        'direction' => 'ASC',
      ),
      'auto_create' => FALSE,
      'default_selection' => 'current',
    );

    $form['target_bundles'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );

    $fields = array(
      'weight' => $this->t('Weight'),
      'label' => $this->t('Name'),
      'hostname' => $this->t('Hostname'),
    );

    $form['sort']['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => $fields,
      '#ajax' => FALSE,
      '#default_value' => $selection_handler_settings['sort']['field'],
    );

    $form['sort']['direction'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sort direction'),
      '#required' => TRUE,
      '#options' => array(
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ),
      '#default_value' => $selection_handler_settings['sort']['direction'],
    );

    return $form;
  }

}
