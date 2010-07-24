<?php
// $Id$

/**
 * @file
 * Domain hooks for Domain Theme.
 *
 * @ingroup domain_theme
 */

/**
 * Implements hook_domain_batch()
 */
function domain_theme_domain_batch() {
  $batch = array();
  // Allows the deletion of all Domain Theme rows.
  $batch['domain_theme'] = array(
    '#form' => array(
      '#title' => t('Reset themes'),
      '#type' => 'checkbox',
      '#options' => array(0 => 1, 1 => t('Reset')),
      '#description' => t('Delete custom theme settings for this domain.'),
    ),
    '#domain_action' => 'domain_delete',
    '#system_default' => 0,
    '#meta_description' => t('Delete custom theme settings for domains as supplied by Domain Theme.'),
    '#table' => 'domain_theme',
    '#weight' => -10,
  );
  // Change themes for sites.
  $themes = list_themes();
  $options = array();
  foreach ($themes as $theme) {
    if ($theme->status == 1) {
      $options[$theme->name] = $theme->name;
    }
  }
  $batch['site_theme'] = array(
    '#form' => array(
      '#title' => t('Theme settings'),
      '#type' => 'select',
      '#options' => $options,
      '#description' => t('Select the theme for this domain.'),
    ),
    '#domain_action' => 'custom',
    '#lookup' => 'domain_theme_lookup',
    '#submit' => 'domain_theme_batch',
    '#system_default' => variable_get('theme_default', 'garland'),
    '#variable' => 'theme_default',
    '#meta_description' => t('Change the themes for all domains.'),
    '#data_type' => 'string',
    '#update_all' => TRUE,
    '#weight' => -9,
  );
  foreach ($batch as $key => $value) {
    $batch[$key]['#module'] = t('Domain Theme');
  }

  return $batch;
}

/**
 * Implements hook_domain_link()
 */
function domain_theme_domain_link($domain) {
  if ($domain['domain_id'] > 0) {
    $links[] = array(
      'title' => t('theme'),
      'path' => 'admin/config/domain/theme/' . $domain['domain_id']
    );
    return $links;
  }
  return FALSE;
}

/**
 * Implements hook_domain_warning()
 */
function domain_theme_domain_warning() {
  $forms =  array(
    'system_themes_form',
    'system_theme_settings',
  );
  $return = array();
  foreach ($forms as $form) {
    $return[$form] =  'admin/config/domain/theme/%domain_id';
  }
  return $return;
}
