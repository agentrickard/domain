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
  foreach ($list as $domain_id => $domain) {
    $record = domain_machine_name_load($domain['machine_name']);
    unset($record['domain_id']);
    $export['features']['domain'][$domain['machine_name']] = $record;
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
  foreach ($data as $name => $value) {
    // When features runs a check for normal / overridden, it passes only
    // array keys, so we account for that.
    if (is_array($value)) {
      $code[] = "  \$domains['{$name}'] = " . features_var_export($value) .";";
    }
    else {
      $record = domain_machine_name_load($value);
      unset($record['domain_id']);
      $code[] = "  \$domains['" . $record['machine_name'] . "'] = " . features_var_export($record) .";";
    }
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
    foreach ($defaults as $domain) {
      $domain['domain_id'] = domain_load_domain_id($domain['machine_name']);
      // This may not be needed.
      if ($domain['domain_id']) {
        $domain['last_id'] = $domain['domain_id'];
      }
      else {
        $domain['last_id'] = 0;
      }
      domain_save($domain, $domain);
      module_invoke_all('domain_features_rebuild', $module, $domain);
    }
  }
}
