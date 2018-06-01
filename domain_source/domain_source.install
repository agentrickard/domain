<?php

/**
 * @file
 * Install, update and uninstall functions for the Domain Access module.
 */

/**
 * Implements hook_install().
 *
 * Installs the domain source field on nodes. We don't do this via schema.yml
 * files because we have an unknown number of node types.
 */
function domain_source_install() {
  if (\Drupal::isConfigSyncing()) {
    // Configuration is assumed to already be checked by the config importer
    // validation events.
    return;
  }
  // Assign domain source to bundles.
  $list = [];
  $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
  foreach ($node_types as $type => $info) {
    $list[$type] = 'node';
  }
  // Install our fields.
  foreach ($list as $bundle => $entity_type) {
    domain_source_confirm_fields($entity_type, $bundle);
  }
}
