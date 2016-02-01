<?php

/**
 * @file
 * Contains \Drupal\domain_config\PathProcessor\DomainConfigPathProcessorFront.
 */

namespace Drupal\domain_config\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class DomainConfigPathProcessorFront implements InboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a DomainPathProcessorFront object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving the site front page configuration.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($path === '/') {
      $path = '/domain_config_homepage';
    }
    return $path;
  }
}
