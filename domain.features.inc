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
  $code[] = '$domains = array();';
  // Check for changes against the target database.
  if (!$export) {
    $data = domain_machine_names();
  }
  foreach ($data as $name) {
    $record = domain_machine_name_load($name);
    unset($record['domain_id']);
    $code[] = "  \$domains['" . $record['machine_name'] . "'] = " . features_var_export($record) .";";
  }
  $code[] = "\nreturn \$domains;";
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
    // Remove domains that are not in the feature.
    $machine_names = array_keys($defaults);
    // If nothing is set, do not execute this routine.
    if (empty($machine_names)) {
      return;
    }
    $domains = domain_domains(TRUE);
    foreach ($domains as $domain) {
      if (!in_array($domain['machine_name'], $machine_names)) {
        domain_delete($domain);
      }
    }
    // Save the domains in this feature.
    foreach ($defaults as $domain) {
      $domain['domain_id'] = domain_load_domain_id($domain['machine_name']);
      domain_save($domain, $domain);
    }
  }
}
