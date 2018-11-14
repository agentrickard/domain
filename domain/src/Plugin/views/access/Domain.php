<?php

namespace Drupal\domain\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainStorageInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides domain-based access control.
 *
 * @ViewsAccess(
 *   id = "domain",
 *   title = @Translation("Domain"),
 *   help = @Translation("Access will be granted when accessed from an allowed domain.")
 * )
 */
class Domain extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Domain negotiation.
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $domainNegotiator;

  /**
   * Constructs a Role object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain storage loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainStorageInterface $domain_storage, DomainNegotiatorInterface $domain_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainStorage = $domain_storage;
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('domain.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $id = $this->domainNegotiator->getActiveId();
    $options = array_filter($this->options['domain']);
    return isset($options[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($this->options['domain']) {
      $route->setRequirement('_domain', (string) implode('+', $this->options['domain']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $count = count($this->options['domain']);
    if ($count < 1) {
      return $this->t('No domain(s) selected');
    }
    elseif ($count > 1) {
      return $this->t('Multiple domains');
    }
    else {
      $domains = $this->domainStorage->loadOptionsList();
      $domain = reset($this->options['domain']);
      return $domains[$domain];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['domain'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['domain'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->options['domain'],
      '#options' => $this->domainStorage->loadOptionsList(),
      '#description' => $this->t('Only the checked domain(s) will be able to access this display.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $domain = $form_state->getValue(['access_options', 'domain']);
    $domain = array_filter($domain);

    if (!$domain) {
      $form_state->setError($form['domain'], $this->t('You must select at least one domain if type is "by domain"'));
    }

    $form_state->setValue(['access_options', 'domain'], $domain);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    foreach (array_keys($this->options['domain']) as $id) {
      if ($domain = $this->domainStorage->load($id)) {
        $dependencies[$domain->getConfigDependencyKey()][] = $domain->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
