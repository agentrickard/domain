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
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_theme_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_theme_default_themes', FALSE);
  $code = array();
  $code[] = '$domain_themes = array();';
  // Check for changes against the target database.
  if (!$export) {
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    $themes = domain_theme_prepare_export($name);
    $code[] = "  \$domain_themes['{$name}'] = " . features_var_export($themes) .";";
  }
  $code[] = "\nreturn \$domain_themes;";
  $output = implode("\n", $code);
  return array('domain_theme_default_themes' => $output);
}

/**
 * Prepares alias lists to an array based on machine_name.
 *
 * @param $machine_name
 *  The source domain machine name.
 *
 * @return
 *  An array of alias records.
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
    // Wipe the old records.
    db_delete('domain_theme')
      ->execute();
    foreach ($defaults as $key => $themes) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Save the new records.
      if (empty($themes)) {
        continue;
      }
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
