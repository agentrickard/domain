<?php

/**
 * @file
 * Features integration for Domain Access.
 */

/**
 * Implements hook_features_export().
 */
function domain_features_export($data, &$export, $module_name) {
  $export['dependencies']['domain'] = 'domain';
  $list = domain_features_selection($data);
  foreach ($list as $machine_name) {
    $export['features']['domain'][$machine_name] = $machine_name;
  }
  domain_features_export_set_wipe_tables($export, $data, 'domain');
  return array();
}

/**
 * Implements hook_features_export_options().
 */
function domain_features_export_options() {
  return domain_features_get_options();
}

/**
 * Implements hook_features_export_render().
 */
function domain_features_export_render($module_name, $data, $export = NULL) {
  domain_features_load($module_name, 'domain_default_domains', FALSE);
  $code = array();
  $code[] = '  $domains = array();';
  // Set the wipe tables item.
  if ($wipe = domain_features_export_wipe_tables_code($data, $code, $export, 'domains') && empty($export)) {
    // Check for changes against the target database.
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    $record = domain_machine_name_load($name);
    if (!empty($record)) {
      unset($record['domain_id']);
      $code[] = "  \$domains['" . $record['machine_name'] . "'] = " . features_var_export($record, '  ') . ";";
    }
  }
  $code[] = "\n  return \$domains;";
  $output = implode("\n", $code);

  return array('domain_default_domains' => $output);
}

/**
 * Implements hook_features_revert().
 */
function domain_features_revert($module) {
  return domain_features_rebuild($module);
}

/**
 * Implements hook_features_rebuild().
 */
function domain_features_rebuild($module) {
  if ($defaults = domain_features_load($module, 'domain_default_domains', TRUE)) {
    // Check for hard rebuild/revert.
    if ($wipe = domain_features_wipe_tables($defaults)) {
      $domains = domain_domains(TRUE);
      foreach ($domains as $domain) {
        if (!isset($defaults[$domain['machine_name']])) {
          domain_delete($domain);
        }
      }
      unset($defaults['wipe-domain-tables']);
    }
    // Save the domains in this feature.
    foreach ($defaults as $key => $domain) {
      if ($id = domain_load_domain_id($key)) {
        $domain['domain_id'] = $id;
      }
      domain_save($domain, $domain);
    }
  }
}
