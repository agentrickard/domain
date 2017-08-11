<?php

namespace Drupal\domain\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainNegotiator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Domain' condition.
 *
 * @Condition(
 *   id = "domain",
 *   label = @Translation("Domain"),
 *   context = {
 *     "entity:domain" = @ContextDefinition("entity:domain", label = @Translation("Domain"), required = FALSE)
 *   }
 * )
 */
class Domain extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $domainNegotiator;

  /**
   * Constructs a Domain condition plugin.
   *
   * @param \Drupal\domain\DomainNegotiator $domain_negotiator
   *   The domain negotiator service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(DomainNegotiator $domain_negotiator, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $container->get('domain.negotiator'),
        $configuration,
        $plugin_id,
        $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['domains'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the following domains are active'),
      '#default_value' => $this->configuration['domains'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', \Drupal::service('domain.loader')->loadOptionsList()),
      '#description' => $this->t('If you select no domains, the condition will evaluate to TRUE for all requests.'),
      '#attached' => array(
        'library' => array(
          'domain/drupal.domain',
        ),
      ),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'domains' => array(),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['domains'] = array_filter($form_state->getValue('domains'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the domain labels. They will be sanitized below.
    $domains = array_intersect_key(\Drupal::service('domain.loader')->loadOptionsList(), $this->configuration['domains']);
    if (count($domains) > 1) {
      $domains = implode(', ', $domains);
    }
    else {
      $domains = reset($domains);
    }
    if ($this->isNegated()) {
      return $this->t('Active domain is not @domains', array('@domains' => $domains));
    }
    else {
      return $this->t('Active domain is @domains', array('@domains' => $domains));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $domains = $this->configuration['domains'];
    if (empty($domains) && !$this->isNegated()) {
      return TRUE;
    }
    // If the context did not load, derive from the request.
    $domain = $this->getContextValue('entity:domain');
    // During tests, we pass an explicit NULL.
    if (empty($domain) && !is_null($domain)) {
      $domain = $this->domainNegotiator->getActiveDomain();
    }
    // No context found?
    if (empty($domain)) {
      return FALSE;
    }
    // NOTE: The context system handles negation for us.
    return (bool) in_array($domain->id(), $domains);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.site';
    return $contexts;
  }

}
