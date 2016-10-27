<?php

namespace Drupal\domain\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\domain\DomainLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;

/**
 * Processes the inbound path by resolving it to the front page if empty.
 *
 */
class PathProcessorDomainHomepage implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var DomainLoader
   */
  protected $domain;

  /**
   * Constructs a PathProcessorFront object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving the site front page configuration.
   * @param \Drupal\domain\DomainLoader $domain
   *   A domain loader service.
   */
  public function __construct(ConfigFactoryInterface $config, DomainLoader $domain) {
    $this->config = $config;
    $this->domain = $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {

    if ($path === '/') {

      $hostname = $request->getHost();
      $domain = $this->domain->loadByHostname($hostname);

      if (!$domain || $domain->isDefault() || !$domain->getHomepage()) {
        $path = $this->config->get('system.site')->get('page.front');
      } else {
        $path = $domain->getHomepage();
      }

      if (empty($path)) {
        throw new NotFoundHttpException();
      }

    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // The special path '<front>' links to the default front page.
    if ($path === '/<front>') {
      $path = '/';
    }
    return $path;
  }

}
