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
  foreach ($list as $domain_id => $domain) {
    $key = domain_load_machine_name($domain_id);
    $record = domain_alias_prepare_export($domain_id);
    $export['features']['domain_alias'][$key] = $record;
  }
  return array();
}

/**
 * Implements hook_features_export_render().
 */
function domain_alias_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_alias_default_aliases', FALSE);
  $code = array();
  $code[] = '$domain_aliases = array();';
  foreach ($data as $name => $value) {
    // When features runs a check for normal / overridden, it passes only
    // array keys, so we account for that.
    if (is_array($value)) {
      $code[] = "  \$domain_aliases['{$name}'] = " . features_var_export($value) .";";
    }
    else {
      $domain_id = domain_load_domain_id($value);
      $list = domain_alias_prepare_export($domain_id);
      $code[] = "  \$domain_aliases['{$value}'] = " . features_var_export($list) .";";
    }
  }
  $code[] = "\nreturn \$domain_aliases;";
  $output = implode("\n", $code);
  return array('domain_alias_default_aliases' => $output);
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
function domain_alias_prepare_export($domain_id) {
  $list = domain_alias_list($domain_id);
  if (!empty($list)) {
    foreach ($list as $id => $val) {
      $list[$id]['machine_name'] = domain_load_machine_name($val['domain_id']);
      unset($list[$id]['domain_id']);
      unset($list[$id]['alias_id']);
    }
  }
  return $list;
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
    foreach ($defaults as $key => $list) {
      $domain_id = domain_load_domain_id($key);
      if (!$domain_id) {
        continue;
      }
      // Wipe the old alias records.
      db_delete('domain_alias')
        ->condition('domain_id', $domain_id)
        ->execute();
      // Save the new alias records.
      if (empty($list)) {
        continue;
      }
      foreach ($list as $alias) {
        $alias['domain_id'] = $domain_id;
        // Note that we don't have to use placeholder_sql functions because
        // these records came straight from the db.
        drupal_write_record('domain_alias', $alias);
      }
    }
  }
}
