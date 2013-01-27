<?php

/**
 * @file
 * Features support for Domain Alias.
 */

/**
 * Implements hook_features_export_options().
 */
function domain_alias_features_export_options() {
  return domain_features_get_options();
}

/**
 * Implements hook_features_export().
 */
function domain_alias_features_export($data, &$export, $module_name) {
  $export['dependencies']['domain_alias'] = 'domain_alias';
  $list = domain_features_selection($data);
  foreach ($list as $machine_name) {
    $export['features']['domain_alias'][$machine_name] = $machine_name;
  }
  domain_features_export_set_wipe_tables($export, $data, 'domain_alias');
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_alias_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_alias_default_aliases', FALSE);
  $code = array();
  $code[] = '  $domain_aliases = array();';
  // Set the wipe tables item.
  if ($wipe = domain_features_export_wipe_tables_code($data, $code, $export, 'domain_aliases') && empty($export)) {
    // Check for changes against the target database.
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    if ($name != 'wipe-domain-tables') {
      $list = domain_alias_prepare_export($name);
      $code[] = "  \$domain_aliases['{$name}'] = " . features_var_export($list, '  ') . ";";
    }
  }
  $code[] = "\n  return \$domain_aliases;";
  $output = implode("\n", $code);
  return array('domain_alias_default_aliases' => $output);
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
function domain_alias_prepare_export($machine_name) {
  $domain_id = domain_load_domain_id($machine_name);
  $list = domain_alias_list($domain_id);
  $aliases = array();
  if (!empty($list)) {
    foreach ($list as $id => $val) {
      $val['machine_name'] = domain_load_machine_name($val['domain_id']);
      unset($val['domain_id']);
      unset($val['alias_id']);
      // Use pattern as the key, since it is machine-readable.
      $aliases[$val['pattern']] = $val;
    }
  }
  return $aliases;
}

/**
 * Implements hook_features_revert().
 */
function domain_alias_features_revert($module) {
  return domain_alias_features_rebuild($module);
}

/**
 * Implements hook_features_rebuild().
 */
function domain_alias_features_rebuild($module) {
  if ($defaults = domain_features_load($module, 'domain_alias_default_aliases', TRUE)) {
    // Check for hard rebuild/revert.
    if ($wipe = domain_features_wipe_tables($defaults)) {
      db_delete('domain_alias')
        ->execute();
      unset($defaults['wipe-domain-tables']);
    }
    foreach ($defaults as $key => $list) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Delete existing alias records.
      db_delete('domain_alias')
        ->condition('domain_id', $domain_id)
        ->execute();
      // Save the new alias records.
      if (!empty($list)) {
        foreach ($list as $alias) {
          $alias['domain_id'] = $domain_id;
          $alias['pattern'] = _domain_alias_placeholders_to_sql($alias['pattern']);
          drupal_write_record('domain_alias', $alias);
        }
      }
    }
  }
}
