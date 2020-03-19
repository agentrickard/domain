<?php

namespace Drupal\domain\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\domain\DomainNegotiator;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract active domain ID.
 *
 * @ViewsArgumentDefault(
 *   id = "active_domain",
 *   title = @Translation("Active domain")
 * )
 */
class Domain extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * Domain negotiation.
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $domainNegotiator;

  /**
   * Constructs a Domain object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\domain\DomainNegotiator $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainNegotiator $domain_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('domain.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->domainNegotiator->getActiveId();
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

}
