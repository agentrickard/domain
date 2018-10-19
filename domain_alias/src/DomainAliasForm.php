<?php

namespace Drupal\domain_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasForm extends EntityForm {

  /**
   * The domain alias validator.
   *
   * @var \Drupal\domain_alias\DomainAliasValidatorInterface
   */
  protected $validator;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The domain entity access control handler.
   *
   * @var \Drupal\domain\DomainAccessControlHandler
   */
  protected $accessHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain storage manager.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The domain alias storage manager.
   *
   * @var \Drupal\domain_alias\DomainAliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Constructs a DomainAliasForm object.
   *
   * @param \Drupal\domain_alias\DomainAliasValidatorInterface $validator
   *   The domain alias validator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\domain_alias\DomainAliasStorageInterface $alias_storage
   *   The alias storage.
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain storage manager.
   */
  public function __construct(DomainAliasValidatorInterface $validator, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DomainAliasStorageInterface $alias_storage, DomainStorageInterface $domain_storage) {
    $this->validator = $validator;
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasStorage = $alias_storage;
    $this->domainStorage = $domain_storage;
    // Not loaded directly since it is not an interface.
    $this->accessHandler = $this->entityTypeManager->getAccessControlHandler('domain');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_alias.validator'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage('domain_alias'),
      $container->get('entity_type.manager')->getStorage('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $alias */
    $alias = $this->entity;

    $form['domain_id'] = [
      '#type' => 'value',
      '#value' => $alias->getDomainId(),
    ];
    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#size' => 40,
      '#maxlength' => 80,
      '#default_value' => $alias->getPattern(),
      '#description' => $this->t('The matching pattern for this alias.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $alias->id(),
      '#machine_name' => [
        'source' => ['pattern'],
        'exists' => '\Drupal\domain_alias\Entity\DomainAlias::load',
      ],
    ];
    $form['redirect'] = [
      '#type' => 'select',
      '#options' => $this->redirectOptions(),
      '#default_value' => $alias->getRedirect(),
      '#description' => $this->t('Set an optional redirect directive when this alias is invoked.'),
    ];
    $environments = $this->environmentOptions();
    $form['environment'] = [
      '#type' => 'select',
      '#options' => $environments,
      '#default_value' => $alias->getEnvironment(),
      '#description' => $this->t('Map the alias to a development environment.'),
    ];
    $form['environment_help'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Environment list'),
      '#description' => $this->t('The table below shows the registered aliases for each environment.'),
    ];

    $domains = $this->domainStorage->loadMultipleSorted();
    $rows = [];
    foreach ($domains as $domain) {
      // If the user cannot edit the domain, then don't show in the list.
      $access = $this->accessHandler->checkAccess($domain, 'update');
      if ($access->isForbidden()) {
        continue;
      }
      $row = [];
      $row[] = $domain->label();
      foreach ($environments as $environment) {
        $match_output = [];
        if ($environment == 'default') {
          $match_output[] = $domain->getCanonical();
        }
        $matches = $this->aliasStorage->loadByEnvironmentMatch($domain, $environment);
        foreach ($matches as $match) {
          $match_output[] = $match->getPattern();
        }
        $output = [
          '#items' => $match_output,
          '#theme' => 'item_list',
        ];
        $row[] = \Drupal::service('renderer')->render($output);
      }
      $rows[] = $row;
    }

    $form['environment_help']['table'] = [
      '#type' => 'table',
      '#header' => array_merge([$this->t('Domain')], $environments),
      '#rows' => $rows,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Returns a list of valid redirect options for the form.
   *
   * @return array
   *   A list of valid redirect options.
   */
  public function redirectOptions() {
    return [
      0 => $this->t('Do not redirect'),
      301 => $this->t('301 redirect: Moved Permanently'),
      302 => $this->t('302 redirect: Found'),
    ];
  }

  /**
   * Returns a list of valid environement options for the form.
   *
   * @return array
   *   A list of valid environment options.
   */
  public function environmentOptions() {
    $list = $this->config->get('domain_alias.settings')->get('environments');
    $environments = [];
    foreach ($list as $item) {
      $environments[$item] = $item;
    }
    return $environments;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $errors = $this->validator->validate($this->entity);
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
      \Drupal::messenger()->addMessage($this->t('Created new domain alias.'));
      $this->logger('domain_alias')->notice('Created new domain alias %name.', ['%name' => $alias->label(), 'link' => $edit_link]);
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Updated domain alias.'));
      $this->logger('domain_alias')->notice('Updated domain alias %name.', ['%name' => $alias->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('domain_alias.admin', ['domain' => $alias->getDomainId()]);
  }

}
