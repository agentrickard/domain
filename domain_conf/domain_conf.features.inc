<?php

/**
 * @file
 * Features support for Domain Conf.
 */

/**
 * Implements hook_features_export_options().
 */
function domain_conf_features_export_options() {
  return domain_features_get_options();
}

/**
 * Implements hook_features_export().
 */
function domain_conf_features_export($data, &$export, $module_name) {
  $export['dependencies']['domain_conf'] = 'domain_conf';
  $list = domain_features_selection($data);
  foreach ($list as $machine_name) {
    $export['features']['domain_conf'][$machine_name] = $machine_name;
  }
  domain_features_export_set_wipe_tables($export, $data, 'domain_conf');
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_conf_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_conf_default_variables', FALSE);
  $code = array();
  $code[] = '  $domain_variables = array();';
  // Set the wipe tables item.
  if ($wipe = domain_features_export_wipe_tables_code($data, $code, $export, 'domain_variables') && empty($export)) {
    // Check for changes against the target database.
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    if ($name != 'wipe-domain-tables') {
      $variables = domain_conf_prepare_export($name);
      $code[] = "  \$domain_variables['{$name}'] = " . features_var_export($variables, '  ') . ";";
    }
  }
  $code[] = "\n  return \$domain_variables;";
  $output = implode("\n", $code);
  return array('domain_conf_default_variables' => $output);
}

/**
 * Prepares domain-specific settings arrays for export.
 *
 * @param $machine_name
 *  The source domain machine name.
 *
 * @return
 *  An array of configuration records.
 */
function domain_conf_prepare_export($machine_name) {
  $domain_id = domain_load_domain_id($machine_name);
  $variables = array();
  $data = db_query("SELECT settings FROM {domain_conf} WHERE domain_id = :domain_id", array(':domain_id' => $domain_id))->fetchField();
  if (!empty($data)) {
    $variables = domain_unserialize($data);
  }
  return $variables;
}

/**
 * Implements hook_features_revert().
 */
function domain_conf_features_revert($module) {
  return domain_conf_features_rebuild($module);
}

/**
 * Implements hook_features_rebuild().
 */
function domain_conf_features_rebuild($module) {
  if ($defaults = domain_features_load($module, 'domain_conf_default_variables', TRUE)) {
    // Check for hard rebuild/revert.
    if ($wipe = domain_features_wipe_tables($defaults)) {
      db_delete('domain_conf')
        ->execute();
      unset($defaults['wipe-domain-tables']);
    }
    foreach ($defaults as $key => $variables) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Delete existing conf records.
      db_delete('domain_conf')
        ->condition('domain_id', $domain_id)
        ->execute();
      // Save the new records.
      if (!empty($variables)) {
        $record = array(
          'domain_id' => $domain_id,
          'settings' => serialize($variables),
        );
        drupal_write_record('domain_conf', $record);
      }
    }
  }
}
