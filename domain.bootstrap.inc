<?php

/**
 * @file
 * Domain bootstrap file.
 *
 * The domain bootstrap process is initiated in domain/settings.inc which is
 * (supposed to be) included in the user's settings.php and therefore initiated
 * during drupal's first bootstrap phase (DRUPAL_BOOTSTRAP_CONFIGURATION).
 *
 * The purpose of this is to allow domain-based modules to modify basic drupal
 * systems like the variable or database/prefix system - before they are used by
 * any other modules.
 *
 * @ingroup domain
 */

/**
 * Domain bootstrap phase 1: makes sure that database is initialized and
 * loads all necessary module files.
 */
define('DOMAIN_BOOTSTRAP_INIT', 0);

/**
 * Domain bootstrap phase 2: resolves host and does a lookup in the {domain}
 * table. Also invokes hook "hook_domain_bootstrap_lookup".
 */
define('DOMAIN_BOOTSTRAP_NAME_RESOLVE', 1);

/**
 * Domain bootstrap phase 3: invokes hook_domain_bootstrap_full().
 */
define('DOMAIN_BOOTSTRAP_FULL', 2);

/**
 * Domain module bootstrap: calls all bootstrap phases.
 *
 * Effectively, we add our own bootstrap phase to Drupal core.
 * This allows us to do selective configuration changes before Drupal
 * has a chance to react. For example, the Domain Conf module allows
 * page caching to be set to "on" for example.com but "off" for
 * no-cache.example.com.
 *
 * The big issues here are command-line interactions, which are slightly
 * different; and the fact that drupal_settings_initialize() is needed to
 * properly hit the page cache.
 *
 * The initial check for GLOBALS['base_root'] lets us know that we have
 * already run the normal configuration routine and are now free to alter
 * settings per-domain.
 *
 * Note that this problem is largely caused by the need to jump ahead
 * in drupal_bootstrap() in order to have database functions available.
 */
function domain_bootstrap() {
  global $_domain;
  // Initialize our global variable.
  $_domain = array();

  // Ensure that core globals are loaded so that we don't break page caching.
  // See http://drupal.org/node/1046844
  if (!isset($GLOBALS['base_root'])) {
    // $conf is set before settings.php is invoked. Do not let $conf get reset to an array().
    // See http://drupal.org/node/1056152
    $settings_conf = $GLOBALS['conf'] ;
    // Sadly, we have to call this function from within itself.
    // Thankfully, include_once prevents us from making major errors.
    drupal_settings_initialize();
    $GLOBALS['conf'] = $settings_conf;
  }

  $phases = array(DOMAIN_BOOTSTRAP_INIT, DOMAIN_BOOTSTRAP_NAME_RESOLVE, DOMAIN_BOOTSTRAP_FULL);
  foreach ($phases as $phase) {
    // We return NULL if the domain module is not enabled.  This prevents
    // load errors during module enable / disable.
    $error = _domain_bootstrap($phase);
    if ($error === NULL) {
      $_domain['error'] = -1;
      break;
    }
    elseif ($error === FALSE) {
      // watchdog() is not available here, and there may be other logging.
      // So we have to write an error message global variable and pick them up
      // in settings,inc.
      $_domain['error'] = $phase;
      break;
    }
  }
}

/**
 * Calls individual bootstrap phases.
 *
 * @param $phase
 *   The domain bootstrap phase to call.
 * @return
 *   Returns TRUE if the bootstrap phase was successful and FALSE otherwise.
 */
function _domain_bootstrap($phase) {
  global $_domain;
  switch ($phase) {
    case DOMAIN_BOOTSTRAP_INIT:
      // Make sure database is loaded.
      // The new update handler causes problems here, so we account for it.
      // Same with drush or other CLI resources.
      $new_phase = FALSE;
      if (function_exists('update_prepare_d7_bootstrap') || drupal_is_cli()) {
        $new_phase = TRUE;
      }
      drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE, $new_phase);

      // If the Domain Access module has been disabled, stop loading.
      $table = domain_get_primary_table('system');
      $check = (bool) db_query("SELECT status FROM $table WHERE name = 'domain' AND type = 'module'")->fetchField();
      if (empty($check)) {
        return NULL;
      }

      // Load bootstrap modules registered by Domain Access.
      _domain_bootstrap_modules_load();

      // If essential core module file has not been loaded, bootstrap fails.
      if (!function_exists('domain_load')) {
        return FALSE;
      }
      break;

    case DOMAIN_BOOTSTRAP_NAME_RESOLVE:
      // Get the domain_id of the request.
      $_domain = domain_resolve_host();
      // If we don't have a valid domain id now, we can't really go on, bootstrap fails.
      if (empty($_domain) || !is_numeric($_domain['domain_id'])) {
        return FALSE;
      }
      break;

    case DOMAIN_BOOTSTRAP_FULL:
      // Make sure the load process worked correctly before invoking the hook.
      if (!domain_settings_setup_ok()) {
        return FALSE;
      }

      // Grant access to all affiliates. See http://drupal.org/node/770650
      $_domain['site_grant'] = DOMAIN_SITE_GRANT;

      _domain_bootstrap_invoke_all('full', $_domain);
      break;
  }

  return TRUE;
}

/**
 * Returns a list of modules which are loaded during domain_bootstrap phases.
 *
 * The domain module is always in the list of modules and has weight -99 so it
 * should usually be first one as well.
 *
 * @param $reset
 *   If set to TRUE the cached list of modules is updated with the value from
 *   the {variable} table again. Default value is FALSE. Optional.
 * @return
 *   An array of module names.
 */
function _domain_bootstrap_modules($reset = FALSE) {
  static $modules = NULL;
  if ($reset || is_null($modules)) {
    $modules = _domain_bootstrap_modules_get();
    if (!is_array($modules)) {
      $modules = array();
    }
    if (!in_array('domain', $modules)) {
      $modules['-99'] = 'domain';
    }
    ksort($modules);
  }
  return $modules;
}

/**
 * Tries to load all domain bootstrap modules.
 * @see _domain_bootstrap_modules()
 */
function _domain_bootstrap_modules_load() {
  $modules = _domain_bootstrap_modules();
  foreach ($modules as $module) {
    drupal_load('module', $module);
  }
}

/**
 * Retrieves the value of the variable 'domain_bootstrap_modules' from the
 * {variable} table. This function does not use Drupal's variable system.
 *
 * @return
 *   An array containing module names.
 */
function _domain_bootstrap_modules_get() {
  global $conf;
  $key = 'domain_bootstrap_modules';
  $table = domain_get_primary_table('variable');
  $result = db_query("SELECT value FROM $table WHERE name = :name", array(':name' => $key))->fetchField();
  if (!empty($result)) {
    $conf[$key] = unserialize($result);
  }
  else {
    $conf[$key] = array('domain');
  }
  return $conf[$key];
}

/**
 * Tries to call specified hook on all domain_bootstrap modules.
 *
 * The hook function names are of the following format:
 * {$module}_domain_bootstrap_{$hook} where {$module}
 * is the name of the module implementing the hook and {$hook}
 * is the identifier for the concrete domain bootstrap hook.
 *
 * This function is basically a copy of module_invoke_all() adjusted to our
 * needs.
 *
 * @link http://api.drupal.org/api/function/module_invoke_all/6
 */
function _domain_bootstrap_invoke_all() {
  $args = func_get_args();
  $hook = $args[0];
  unset($args[0]);
  $return = array();
  foreach (_domain_bootstrap_modules() as $module) {
    $function = $module . '_domain_bootstrap_' . $hook;
    if (function_exists($function)) {
      $result = call_user_func_array($function, $args);
      if (isset($result) && is_array($result)) {
        $return = array_merge_recursive($return, $result);
      }
      elseif (isset($result)) {
        $return[] = $result;
      }
    }
  }
  return $return;
}

/**
 * Escape the names of the default tables for variable lookups.
 *
 * There are a few cases where we must directly query data from the
 * primary site's database. Due to table prefixing and Domain Prefix, we
 * must ensure that we are querying the correct table.
 *
 * When using this function, you should insert the $table result directly
 * into your query string, or use token replacement '%s' syntax. Do not
 * enclose the table name in brackets {}, as that defeats the purpose of
 * this function.
 *
 * @see _domain_conf_load_primary()
 *
 * @param $table
 *   The name of the base table to lookup.
 * @return
 *   A query-safe table name pointing to the primary domain and default
 *   database..
 */
function domain_get_primary_table($table) {
  global $databases;
  $db_prefix = isset($databases['default']['default']['prefix']) ? $databases['default']['default']['prefix'] : '';
  if (is_string($db_prefix)) {
    $table = $db_prefix . $table;
  }
  elseif (is_array($db_prefix)) {
    $table = $db_prefix['default'] . $table;
  }
  return db_escape_table($table);
}
