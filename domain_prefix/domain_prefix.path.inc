<?php

/**
 * @file
 * Implement hook_domainpath().
 * This is a resource-intensive function, so we only include it
 * if an alias of the {url_alias} table is present.
 */

/**
 * Implement hook_domainpath().
 */
function domain_prefix_domainpath($domain_id, &$path, $path_language = NULL) {
  global $language, $_domain;
  // $map[$domain_id] keys are Drupal paths and the values are the corresponding aliases
  static $map = array(), $count = array(), $status = array(), $tablename = array();

  // If the alias is for the current domain, do nothing.
  if ($domain_id == $_domain['domain_id']) {
    $path = drupal_get_path_alias($path);
    return;
  }

  $path_language = (!empty($path_language)) ? $path_language : $language->language;

  // The default domain does not use a prefix.
  if ($domain_id == 0 && !isset($status[0])) {
    $status[0] = db_table_exists('url_alias');
    $tablename[0] = 'url_alias';
  }

  // Check to see if table prefixing has been done for the {url_alias} table.
  if (!isset($status[$domain_id])) {
    $prefix = domain_prefix_string($domain_id);
    $status[$domain_id] = domain_prefix_table_exists($prefix, 'url_alias');
    $tablename[$domain_id] = $prefix .'url_alias';
  }

  if (!$status[$domain_id]) {
    return;
  }

  // Use $count[$domain_id] to avoid looking up paths in subsequent calls if there are no aliases
  if (!isset($count[$domain_id])) {
    $count[$domain_id] = db_result(db_query('SELECT COUNT(pid) FROM {%s}', $tablename[$domain_id]));
  }

  if ($count[$domain_id] > 0) {
    if (!isset($map[$domain_id][$path_language][$path])) {
      $alias = db_result(db_query("SELECT dst FROM {%s} WHERE src = '%s'", $tablename[$domain_id], $path));
      $map[$domain_id][$path_language][$path] = $alias ? $alias : $path;
    }
    $path = $map[$domain_id][$path_language][$path];
  }
}
