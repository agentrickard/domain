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
  foreach ($list as $domain_id => $domain) {
    $key = domain_load_machine_name($domain_id);
    $record = domain_theme_prepare_export($domain_id);
    $export['features']['domain_theme'][$key] = $record;
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
  foreach ($data as $name => $value) {
    // When features runs a check for normal / overridden, it passes only
    // array keys, so we account for that.
    if (is_array($value)) {
      $code[] = "  \$domain_themes['{$name}'] = " . features_var_export($value) .";";
    }
    else {
      $domain_id = domain_load_domain_id($value);
      $themes = domain_theme_prepare_export($domain_id);
      $code[] = "  \$domain_themes['{$value}'] = " . features_var_export($themes) .";";
    }
  }
  $code[] = "\nreturn \$domain_themes;";
  $output = implode("\n", $code);
  return array('domain_theme_default_themes' => $output);
}

/**
 * Prepares alias lists to an array based on machine_name.
 *
 * @param $domain_id
 *  The source domain id.
 *
 * @return
 *  An array of alias records.
 */
function domain_theme_prepare_export($domain_id) {
  $data = db_query("SELECT domain_id, theme, settings, status, filepath FROM {domain_theme} WHERE domain_id = :domain_id", array(':domain_id' => $domain_id))->fetchAll();
  $theme = array();
  $machine_name = domain_load_machine_name($domain_id);
  foreach ($data as $item) {
    $theme[$machine_name][$item->theme] = array(
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
    foreach ($defaults as $key => $themes) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Wipe the old alias records.
      db_delete('domain_theme')
        ->condition('domain_id', $domain_id)
        ->execute();
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
          'filename' => $theme['filename'],
        );
        drupal_write_record('domain_theme', $record);
      }
    }
  }
}
