<?php

namespace Drupal\domain_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain_alias\DomainAliasLoaderInterface;
use Drupal\domain_alias\DomainAliasValidatorInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasForm extends EntityForm {

  /**
   * @var \Drupal\domain\DomainAliasValidatorInterface
   */
  protected $validator;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var \Drupal\domain\DomainLoaderInterface $loader
   */
  protected $domainLoader;

  /**
   * @var \Drupal\domain_alias\DomainAliasLoaderInterface $alias_loader
   */
  protected $aliasLoader;

  /**
   * Constructs a DomainAliasForm object.
   *
   * @param \Drupal\domain\DomainAliasValidatorInterface $validator
   *   The domain alias validator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The configuration factory service.
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain_alias\DomainAliasLoaderInterface $alias_loader
   *   The alias loader.
   */
  public function __construct(DomainAliasValidatorInterface $validator, ConfigFactoryInterface $config, DomainLoaderInterface $loader, DomainAliasLoaderInterface $alias_loader) {
    $this->validator = $validator;
    $this->config = $config;
    $this->domainLoader = $loader;
    $this->aliasLoader = $alias_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_alias.validator'),
      $container->get('config.factory'),
      $container->get('domain.loader'),
      $container->get('domain_alias.loader')
    );
  }

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
      '#description' => $this->t('Set an optional redirect directive when this alias is invoked.'),
    );
    $environments = $this->environmentOptions();
    $form['environment'] = array(
      '#type' => 'select',
      '#options' => $environments,
      '#default_value' => $alias->getEnvironment(),
      '#description' => $this->t('Creates matched sets of aliases for use during development.'),
    );

    $form['environment_help'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Environment list'),
    ];

    $domains = $this->domainLoader->loadMultiple();
    $rows = [];
    foreach ($domains as $domain) {
      $row = [];
      // @TODO: access checking.
      $row[] = $domain->getHostname();
      foreach ($environments as $environment) {
        $matches = $this->aliasLoader->loadByEnvironmentMatch($domain, $environment);
        $match_output = '';
        foreach ($matches as $match) {
          // @TODO: better handling of arrays.
          $match_output .= $match->getPattern();
        }
        $row[] = $match_output;
      }
      $rows[] = $row;
    }

    $form['environment_help']['table'] = [
      '#type' => 'table',
      '#header' => array_merge([$this->t('Domains')], $environments),
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
    return array(
      0 => $this->t('Do not redirect'),
      301 => $this->t('301 redirect: Moved Permanently'),
      302 => $this->t('302 redirect: Found'),
    );
  }

  /**
   * Returns a list of valid environement options for the form.
   *
   * @return array
   *   A list of valid environment options.
   */
  public function environmentOptions() {
    // @TODO: possibly move this method.
    $list = $this->config->get('domain_alias')->get('environments');
    if (empty($list)) {
      $list = [
        'default',
        'local',
        'development',
        'staging',
        'testing'
      ];
    }
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
      drupal_set_message($this->t('Created new domain alias.'));
      $this->logger('domain_alias')->notice('Created new domain alias %name.', array('%name' => $alias->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Updated domain alias.'));
      $this->logger('domain_alias')->notice('Updated domain alias %name.', array('%name' => $alias->label(), 'link' => $edit_link));
    }
    $form_state->setRedirect('domain_alias.admin', array('domain' => $alias->getDomainId()));
  }

}
