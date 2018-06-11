<?php

/**
 * @file
 * API documentation file for Domain Source module.
 */

/**
 * Allows modules to specify the target domain for an entity.
 *
 * There is no return value for this hook. Modify $source by reference by
 * loading a valid domain record or set $source = NULL to discard an existing
 * $source value and not rewrite the path.
 *
 * Note that $options['entity'] is the entity for the path request and
 * $options['entity_type'] is the type of entity (e.g. 'node').
 * These values have already been verified before this hook is called.
 *
 * If the entity's path is a translation, the requested translation of the
 * entity will be passed as the $entity value.
 *
 * @param \Drupal\domain\Entity\Domain|null &$source
 *   A domain object or NULL if not set.
 * @param string $path
 *   The outbound path request.
 * @param array $options
 *   The options for the url, as defined by
 *   \Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */
function hook_domain_source_alter(array &$source, $path, array $options) {
  // Always link to the default domain.
  $source = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
}

/**
 * Allows modules to specify the target link for a Drupal path.
 *
 * Note: This hook is not meant to be used for node or entity paths, which
 * are handled by hook_domain_source_alter(). This hook is split
 * from hook_domain_source_alter() for better performance.
 *
 * Note that hook_domain_source_alter() only paths that are not content
 * entities.
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
function hook_domain_source_path_alter(array &$source, $path, array $options) {
  // Always make admin links go to the primary domain.
  $parts = explode('/', $path);
  if (isset($parts[0]) && $parts[0] == 'admin') {
    $source = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
  }
}
