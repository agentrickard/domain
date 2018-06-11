<?php

/**
 * @file
 * Install, update and uninstall functions for the Domain Access module.
 */

/**
 * Implements hook_install().
 *
 * Installs the default domain field on nodes. We don't do this via schema.yml
 * files because we have an unknown number of node types.
 */
function domain_access_install() {
  if (\Drupal::isConfigSyncing()) {
    // Configuration is assumed to already be checked by the config importer
    // validation events.
    return;
  }
  // Assign domain access to bundles.
  $list['user'] = 'user';

  $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
  foreach ($node_types as $type => $info) {
    $list[$type] = 'node';
  }
  // Install our fields.
  foreach ($list as $bundle => $entity_type) {
    domain_access_confirm_fields($entity_type, $bundle);
  }
  // Install our actions.
  $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
  foreach ($domains as $domain) {
    domain_access_domain_insert($domain);
  }
}

/**
 * Add the setting to open the domain access fieldset.
 */
function domain_access_update_8001() {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('domain_access.settings');
  $config->set('node_advanced_tab_open', 0);
  $config->save(TRUE);
}
