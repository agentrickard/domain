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
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_conf_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_conf_default_variables', FALSE);
  $code = array();
  $code[] = '$domain_variables = array();';
  // Check for changes against the target database.
  if (!$export) {
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    $variables = domain_conf_prepare_export($name);
    $code[] = "  \$domain_variables['{$name}'] = " . features_var_export($variables) .";";
  }
  $code[] = "\nreturn \$domain_variables;";
  $output = implode("\n", $code);
  return array('domain_conf_default_variables' => $output);
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
    // Wipe the old alias records.
    db_delete('domain_conf')
      ->execute();
    foreach ($defaults as $key => $variables) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Save the new records.
      if (empty($variables)) {
        continue;
      }
      $record = array(
        'domain_id' => $domain_id,
        'settings' => serialize($variables),
      );
      drupal_write_record('domain_conf', $record);
    }
  }
}
