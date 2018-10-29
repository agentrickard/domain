<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for operations to change domain assignments.
 */
abstract class DomainAccessActionBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

  /**
   * The user role entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getDefinition('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'domain_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadOptionsList();
    $form['domain_id'] = [
      '#type' => 'checkboxes',
      '#title' => t('Domain'),
      '#options' => $domains,
      '#default_value' => $this->configuration['id'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['domain_id'] = $form_state->getValue('domain_id');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (!empty($this->configuration['domain_id'])) {
      $prefix = $this->entityType->getConfigPrefix() . '.';
      $this->addDependency('config', $prefix . $this->configuration['domain_id']);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    // @TODO: fix this logic.
    $access = $object->access('update', $account, TRUE);

    return $return_as_object ? $access : $access->isAllowed();
  }

}
