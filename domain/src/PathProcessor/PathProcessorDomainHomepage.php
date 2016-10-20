<?php

namespace Drupal\domain\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\node\Entity\Node;

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
   * Constructs a PathProcessorFront object.
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
      $hostname = $request->getHost();

      /** @var \Drupal\domain\DomainInterface $domain */
      $domain = \Drupal::service('domain.loader')->loadByHostname($hostname);
      $domain->getHomepage();

      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', [
        'node' => $domain->getHomepage()]
      );
      $path = $url->toString();

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
