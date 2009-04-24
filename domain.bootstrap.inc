<?php
// $Id$
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
 * Note that all functions in this file are _private, by design.  You should never
 * call these functions directly.  Public functions are declared in domain.module.
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
 * Domain bootstrap phase 3: invokes bootstrap hook "hook_domain_bootstrap_full".
 */
define('DOMAIN_BOOTSTRAP_FULL', 2);

/**
 * Domain module bootstrap: calls all bootstrap phases.
 */
function domain_bootstrap() {
  global $_domain;
  if (!isset($_domain)) {
    $_domain = array();
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
    else if ($error === FALSE) {
      // watchdog() is not available here, and there may be other logging.
      // So we have to write an error message global variable and pick them up in settings,inc.
      $_domain['error'] = $phase;
      break;
    }
  }
  if (!isset($_domain['error'])) {
    // All the load phases worked, so load custom_url_rewrite_outbound().
    include 'settings_custom_url.inc';
  }
}

/**
 * Calls individual bootstrap phases.
 *
 * @param $phase
 * The domain bootstrap phase to call.
 *
 * @return
 * Returns TRUE if the bootstrap phase was successful and FALSE otherwise.
 */
function _domain_bootstrap($phase) {
  global $_domain;
  switch ($phase) {
    case DOMAIN_BOOTSTRAP_INIT:
      // Make sure database is loaded
      _drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
      // If the module has been disabled, stop loading.
      $check = db_result(db_query("SELECT status FROM {system} WHERE name = 'domain' AND type = 'module'"));
      if (empty($check)) {
        return NULL;
      }

      // Load bootstrap modules registered by Domain Access.
      _domain_bootstrap_modules_load();
      // if essential core module file has not been loaded, bootstrap fails.

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
 * If set to TRUE the cached list of modules is updated with the value from the
 * {variable} table again. Default value is FALSE. Optional.
 *
 * @return
 * An array of module names.
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
 * An array containing module names.
 */
function _domain_bootstrap_modules_get() {
  global $conf;
  $key = 'domain_bootstrap_modules';
  $conf[$key] = unserialize(db_result(db_query("SELECT value FROM {variable} WHERE name = '%s'", $key)));
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
 * @param $hook
 * The name of the bootstrap hook to invoke.
 *
 * ... Arguments to pass to the hook.
 *
 * @link http://api.drupal.org/api/function/module_invoke_all/6
 */
function _domain_bootstrap_invoke_all() {
  $args = func_get_args();
  $hook = $args[0];
  unset($args[0]);
  $return = array();
  foreach (_domain_bootstrap_modules() as $module) {
    $function = $module .'_domain_bootstrap_'. $hook;
    if (function_exists($function)) {
      $result = call_user_func_array($function, $args);
      if (isset($result) && is_array($result)) {
        $return = array_merge_recursive($return, $result);
      }
      else if (isset($result)) {
        $return[] = $result;
      }
    }
  }
  return $return;
}
