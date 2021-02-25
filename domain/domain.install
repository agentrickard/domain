<?php

use Drupal\domain\DomainInterface;

/**
 * @file
 * Install, update and uninstall functions for the Domain Access module.
 */

/**
 * Implements hook_install().
 *
 * Installs the domain admin field on users.
 */
function domain_install() {
  _domain_configure_field();
}

/**
 * Implements hook_uninstall().
 *
 * Core does not properly purge field provided by configuration entities.
 * There are a few related issues and @todo notices in core to this effect.
 * Instead, we handle field purges ourselves.
 */
function domain_uninstall() {
  // Do a pass of purging on deleted Field API data, if any exists.
  $limit = \Drupal::config('field.settings')->get('purge_batch_size');
  field_purge_batch($limit);
  \Drupal::entityTypeManager()->clearCachedDefinitions();
}

/**
 * Implements hook_update_N().
 *
 * Installs the domain admin field on users.
 */
function domain_update_8001() {
  _domain_configure_field();
}

/**
 * Configures user form display to checkboxes widget for domain admin field.
 */
function _domain_configure_field() {
  if ($display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.default')) {
    $display->setComponent(DomainInterface::DOMAIN_ADMIN_FIELD, [
      'type' => 'options_buttons',
      'weight' => 50,
    ])->save();
  }
}

/**
 * Updates block domain context_mapping for Drupal 8.8 and higher.
 */
function domain_update_8002() {
  $new_context_id = '@domain.current_domain_context:domain';
  $config_factory = \Drupal::configFactory();
  $update_list = [];
  foreach ($config_factory->listAll('block.block.') as $block_config_name) {
    $update_block = FALSE;
    $block = $config_factory->getEditable($block_config_name);
    if ($visibility = $block->get('visibility')) {
      foreach ($visibility as $condition_plugin_id => &$condition) {
        if ($condition_plugin_id == 'domain' && (empty($condition['context_mapping']['domain']) || $condition['context_mapping']['domain'] !== $new_context_id)) {
          $condition['context_mapping']['domain'] = $new_context_id;
          $update_block = TRUE;
          $update_list[] = $block_config_name;
        }
      }
    }
    if ($update_block) {
      $block->set('visibility', $visibility);
      $block->save(TRUE);
    }
  }
  if (empty($update_list)) {
    return t('No blocks updated.');
  }
  else {
    return t('Updated @count blocks: @blocks', ['@count' => count($update_list), '@blocks' => implode(', ', $update_list)]);
  }
}

/**
 * Ensure that the update to block visibility was applied properly.
 */
function domain_update_8003() {
  domain_update_8002();
}
