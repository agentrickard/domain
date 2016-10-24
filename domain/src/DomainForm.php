<?php

namespace Drupal\domain;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainLoader;
use Drupal\domain\DomainValidator;
use Drupal\domain\DomainCreator;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Base form for domain edit forms.
 */
class DomainForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * @var \Drupal\domain\DomainValidator
   */
  protected $domainValidator;

  /**
   * @var \Drupal\domain\DomainCreator
   */
  protected $domainCreator;

  /**
   * @var PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * DomainForm constructor.
   *
   * @param \Drupal\domain\DomainValidator $domainValidator
   * @param \Drupal\domain\DomainLoader $domainLoader
   * @param \Drupal\domain\DomainCreator $domainCreator
   * @param PathValidatorInterface $pathValidator
   */
  public function __construct(DomainValidator $domainValidator, DomainLoader $domainLoader, DomainCreator $domainCreator, PathValidatorInterface $pathValidator) {
    $this->domainValidator = $domainValidator;
    $this->domainLoader = $domainLoader;
    $this->domainCreator = $domainCreator;
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain.validator'),
      $container->get('domain.loader'),
      $container->get('domain.creator'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $this->entity;
    $domains = $this->domainLoader->loadMultiple();

    // Create defaults if this is the first domain.
    if (empty($domains)) {
      $domain->addProperty('hostname', $this->domainCreator->createHostname());
      $domain->addProperty('name', $this->config('system.site')->get('name'));
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
      '#delta' => count($this->domainLoader->loadMultiple()) + 1,
      '#default_value' => $domain->getWeight(),
      '#description' => $this->t('The sort order for this record. Lower values display first.'),
    );
    $form['is_default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Default domain'),
      '#default_value' => $domain->isDefault(),
      '#description' => $this->t('If a URL request fails to match a domain record, the settings for this domain will be used. Only one domain can be default.'),
    );
    $form['homepage'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('homepage'),
      '#target_type' => 'node',
      '#tags' => FALSE,
      '#default_value' => Node::load($domain->getHomepage()),
      '#description' => $this->t('Define the homepage'),
    ];

    $required = $this->domainValidator->getRequiredFields();
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
    $errors = $this->domainValidator->validate($entity);
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
