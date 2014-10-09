<?php

/**
 * @file
 * Contains Drupal\domain\HttpKernel\DomainPathProcessor.
 */

namespace Drupal\domain\HttpKernel;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the outbound path using path alias lookups.
 */
class DomainPathProcessor implements OutboundPathProcessorInterface {

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainPathProcessor object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler) {
    $this->domainNegotiator = $negotiator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements Drupal\Core\PathProcessor\OutboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    static $active_domain;
    if (!isset($active_domain)) {
      $active_domain = $this->domainNegotiator->negotiateActiveDomain();
    }

    // Only act on valid internal paths.
    if (empty($path) || !empty($options['external'])) {
      return $path;
    }

    $source = NULL;
    $options['active_domain'] = $active_domain;

    // One hook for nodes.
    if (isset($options['entity_type']) && $options['entity_type'] == 'node') {
      $this->moduleHandler->alter('domain_source', $source, $path, $options);
    }
    // One for other, because the latter is resource-intensive.
    else {
      $this->moduleHandler->alter('domain_source_path', $source, $path, $options);
    }

    // If a source domain is specified, rewrite the link.
    if (!empty($source->path)) {
      $options['base_url'] = $source->path;
      $options['absolute'] = TRUE;
      // @TODO: we may need the port-checking code from PathProcessorLanguage.
    }
    return $path;
  }

}

