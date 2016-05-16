<?php

/**
 * @file
 * API documentation file for Domain Source module.
 */

/**
 * Allows modules to specify the target domain for a node.
 *
 * There is no return value for this hook. Modify $source by reference by
 * loading a valid domain record or set $source = NULL to discard an existing
 * $source value and not rewrite the path. *
 *
 * Note that $options['entity'] is the node for the path request and
 * $options['entity_type'] is the type of entity. These values have already
 * been verified before this hook is called.
 *
 * @param \Drupal\domain\Entity\Domain|NULL &$source
 *   A domain object or NULL if not set.
 * @param string $path
 *   The outbound path request.
 * @param array $options
 *   The options for the url, as defined by
 *   \Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */
function hook_domain_source_alter(&$source, $path, $options) {
  // Always link to the default domain.
  $source = \Drupal::service('domain.loader')->loadDefaultDomain();
}

/**
 * Allows modules to specify the target link for a Drupal path.
 *
 * Note: This hook is not meant to be used for node paths, which
 * are handled by hook_domain_source_alter(). This hook is split
 * from hook_domain_source_alter() for better performance.
 *
 * Note that hook_domain_source_alter() only applies to nodes. It is possible
 * that other entities may be passed here.  If set, $options['entity'] is the
 * entity for the path request and $options['entity_type'] is its type.
 * These values have _not_ been verified before this hook is called.
 *
 * Currently, no modules in the package implement this hook.
 *
 * There is no return value for this hook. Modify $source by reference by
 * loading a valid domain record or set $source = NULL to discard an existing
 * $source value and not rewrite the path.
 *
 * @param array &$source
 *   The domain array from domain_get_node_match(), passed by reference.
 * @param string $path
 *   The outbound path request.
 * @param array $options
 *   The options for the url, as defined by
 *   \Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */
function hook_domain_source_path_alter(&$source, $path, $options) {
  // Always make admin links go to the primary domain.
  $parts = explode('/', $path);
  if (isset($parts[0]) && $parts[0] == 'admin') {
    $source = \Drupal::service('domain.loader')->loadDefaultDomain();
  }
}
