<?php

namespace Drupal\domain\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\domain\DomainLoader;
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
   * The role storage.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * Constructs a Role object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainLoader $domain_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainLoader = $domain_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('domain.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($this->options['domain']) {
      $route->setRequirement('_domain', (string) implode('+', $this->options['domain']));
    }
  }

  public function summaryTitle() {
    $count = count($this->options['domain']);
    if ($count < 1) {
      return $this->t('No domain(s) selected');
    }
    elseif ($count > 1) {
      return $this->t('Multiple domains');
    }
    else {
      $domains = user_role_names();
      $domain = reset($this->options['domain']);
      return $domains[$domain];
    }
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['domain'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['domain'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->options['domain'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('Only the checked domain(s) will be able to access this display.'),
    );
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $domain = $form_state->getValue(array('access_options', 'domain'));
    $domain = array_filter($domain);

    if (!$domain) {
      $form_state->setError($form['domain'], $this->t('You must select at least one domain if type is "by domain"'));
    }

    $form_state->setValue(array('access_options', 'domain'), $domain);
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
