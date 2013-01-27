<?php

/**
 * @file
 * Features support for Domain Theme.
 */

/**
 * Implements hook_features_export_options().
 */
function domain_theme_features_export_options() {
  return domain_features_get_options();
}

/**
 * Implements hook_features_export().
 */
function domain_theme_features_export($data, &$export, $module_name) {
  $export['dependencies']['domain_theme'] = 'domain_theme';
  $list = domain_features_selection($data);
  foreach ($list as $machine_name) {
    $export['features']['domain_theme'][$machine_name] = $machine_name;
  }
  domain_features_export_set_wipe_tables($export, $data, 'domain_theme');
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_theme_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_theme_default_themes', FALSE);
  $code = array();
  $code[] = '  $domain_themes = array();';
  // Set the wipe tables item.
  if ($wipe = domain_features_export_wipe_tables_code($data, $code, $export, 'domain_themes') && empty($export)) {
    // Check for changes against the target database.
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    if ($name != 'wipe-domain-tables') {
      $themes = domain_theme_prepare_export($name);
      $code[] = "  \$domain_themes['{$name}'] = " . features_var_export($themes, '  ') . ";";
    }
  }
  $code[] = "\n  return \$domain_themes;";
  $output = implode("\n", $code);
  return array('domain_theme_default_themes' => $output);
}

/**
 * Prepares theme data as an export array.
 *
 * @param $machine_name
 *  The source domain machine name.
 *
 * @return
 *  An array of theme records.
 */
function domain_theme_prepare_export($machine_name) {
  $domain_id = domain_load_domain_id($machine_name);
  $data = db_query("SELECT domain_id, theme, settings, status, filepath FROM {domain_theme} WHERE domain_id = :domain_id", array(':domain_id' => $domain_id))->fetchAll();
  $theme = array();
  $machine_name = domain_load_machine_name($domain_id);
  foreach ($data as $item) {
    $theme[$item->theme] = array(
      'theme' => $item->theme,
      'settings' => domain_unserialize($item->settings),
      'status' => $item->status,
      'filepath' => $item->filepath,
    );
  }
  return $theme;
}

/**
 * Implements hook_features_revert().
 */
function domain_theme_features_revert($module) {
  return domain_theme_features_rebuild($module);
}

/**
 * Implements hook_features_rebuild().
 */
function domain_theme_features_rebuild($module) {
  if ($defaults = domain_features_load($module, 'domain_theme_default_themes', TRUE)) {
    // Check for hard rebuild/revert.
    if ($wipe = domain_features_wipe_tables($defaults)) {
      db_delete('domain_theme')
        ->execute();
      unset($defaults['wipe-domain-tables']);
    }
    foreach ($defaults as $key => $themes) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Delete existing theme settings.
      db_delete('domain_theme')
        ->condition('domain_id', $domain_id)
        ->execute();
      // Save the new theme settings.
      if (!empty($themes)) {
        foreach ($themes as $theme) {
          $record = array(
            'domain_id' => $domain_id,
            'theme' => $theme['theme'],
            'settings' => serialize($theme['settings']),
            'status' => $theme['status'],
            'filepath' => $theme['filepath'],
          );
          drupal_write_record('domain_theme', $record);
        }
      }
    }
  }
}
