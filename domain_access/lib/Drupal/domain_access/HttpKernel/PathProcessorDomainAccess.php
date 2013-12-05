<?php

/**
 * @file
 * Contains Drupal\domain_access\HttpKernel\PathProcessorDomainAccess.
 */

namespace Drupal\domain_access\HttpKernel;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorDomainAccess implements OutboundPathProcessorInterface {

  /**
   * @var \Drupal\domain\DomainManagerInterface
   */
  protected $domainManager;

  /**
   * Constructs a PathProcessorDomainAccess object.
   *
   * @param \Drupal\domain\DomainManagerInterface $domain_manager
   *   The domain manager service.
   */
  public function __construct(DomainManagerInterface $domain_manager) {
    $this->domainManager = $domain_manager;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    // Only act on node paths.
    if (empty($path) || !empty($options['external']) || !isset($options['entity_type']) || $options['entity_type'] != 'node') {
      return $path;
    }
    static $active_domain;
    if (!isset($active_domain)) {
      $active_domain = $this->domainManager->getActiveDomain();
    }
    // Get the list and sort.
    $list = domain_access_get_node_values($options['entity']);
    $domains = domain_load_and_sort(array_keys($list));

    // TODO: alter the path.

    // TODO: set the canonical domain

    // TODO: cache the canonical domain
    return $path;
  }

}

