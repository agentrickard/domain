<?php

namespace Drupal\domain_config_hook_test\PageCache\RequestPolicy;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A page cache request policy.
 *
 * This service is not meant to DO anything, it's just meant to represent
 * a service that might be present in the Drupal community. For example,
 * persistent_login module has this same structure.
 */
class PageCacheRequestPolicy implements RequestPolicyInterface {

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Drupal config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    // This line is important. You have to use this service for it to fail.
    $this->configFactory
      ->get('system.site');

    return NULL;
  }

}
