<?php

/**
 * @file
 * Domain hooks for Domain Theme.
 *
 * @ingroup domain_theme
 */

/**
 * Implements hook_domain_batch().
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
    '#permission' => 'administer themes',
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
    '#permission' => 'administer themes',
    '#domain_action' => 'custom',
    '#lookup' => 'domain_theme_batch_lookup',
    '#submit' => 'domain_theme_batch_submit',
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
 * Implements hook_domain_warning().
 */
function domain_theme_domain_warning() {
  $forms =  array(
    'system_theme_settings',
  );
  $return = array();
  $theme = arg(3);
  if (empty($theme)) {
    return array();
  }
  foreach ($forms as $form) {
    $return[$form] =  'admin/structure/domain/view/%domain_id/theme/' . $theme . '/theme-settings';
  }
  return $return;
}

/**
 * Implements hook_domain_delete().
 */
function domain_theme_domain_delete($domain, $form_values = array()) {
  db_delete('domain_theme')
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
}

/**
 * Return a theme setting for batch processing.
 *
 * @param $domain
 *   The domain being processed.
 * @return
 *   A valid theme name or -1 on failure.
 */
function domain_theme_batch_lookup($domain) {
  $theme = domain_theme_lookup($domain['domain_id']);
  if (isset($theme['theme'])) {
    return $theme['theme'];
  }
  return -1;
}
