<?php

/**
 * @file
 * Install, update and uninstall functions for the Domain Alias module.
 */

/**
 * Sets the default set of environments.
 */
function domain_alias_set_environments() {
  $config = \Drupal::service('config.factory')->getEditable('domain_alias.settings');
  // Set and save new message value.
  $environments = ['default', 'local', 'development', 'staging', 'testing'];
  $config->set('environments', $environments)->save();
}

/**
 * Updates domain_alias schema.
 */
function domain_alias_update_8001() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  // Regenerate entity type indexes.
  $manager->updateEntityType($manager->getEntityType('domain_alias'));
}

/**
 * Adds domain_alias environment settings.
 */
function domain_alias_update_8002() {
  // Set the default environments variable.
  domain_alias_set_environments();
}
