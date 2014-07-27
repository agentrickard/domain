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
  // See http://drupal.org/node/1046844 and http://drupal.org/node/1189916.
  if (!isset($GLOBALS['base_root'])) {
    domain_settings_initialize();
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
  global $install_state;

  switch ($phase) {
    case DOMAIN_BOOTSTRAP_INIT:
      // Make sure database is loaded.
      // The new update handler causes problems here, so we account for it.
      // Same with drush or other CLI resources. It turns out that the
      // drupal_is_cli() function is not consistent for php and drush scripts,
      // so instead, we check to see if the database driver has been loaded.
      // See http://drupal.org/node/1342740 for the latest background.
      $new_phase = FALSE;
      // If running from drush or update.php, we act differently.
      // Prevent $new_phase = TRUE when running drush site-install by checking
      // the $install_state global.
      if ((function_exists('drush_verify_cli') || function_exists('update_prepare_d7_bootstrap')) && empty($install_state)) {
        $new_phase = TRUE;
      }
      drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE, $new_phase);

      // If the Domain Access module has been disabled, or if we're unable to interact with the database, stop loading.
      if (function_exists('db_query')) {
        $table = domain_get_primary_table('system');
        $check = (bool) db_query("SELECT status FROM $table WHERE name = 'domain' AND type = 'module'")->fetchField();
        if (empty($check)) {
          return NULL;
        }
      }
      else {
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
      if (empty($_domain['domain_id']) || !is_numeric($_domain['domain_id'])) {
        return FALSE;
      }
      break;

    case DOMAIN_BOOTSTRAP_FULL:
      // Grant access to all affiliates. See http://drupal.org/node/770650
      $_domain['site_grant'] = DOMAIN_SITE_GRANT;

      // Load all bootstrap processes registered with the module.
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
  $modules = &drupal_static(__FUNCTION__, NULL);
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
 * into your query string, or use token replacement syntax. Do not
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
  // db_escape_table() relies on a database connection, so we mirror that public
  // method here. See DatabaseConnection::escapeTable.
  return preg_replace('/[^A-Za-z0-9_.]+/', '', $table);
}

/**
 * Replaces the logic from drupal_settings_initialize() so caching works.
 *
 * Problem: we cannot run drupal_settings_initialize() twice, so this logic
 * has to be cloned here, otherwise, user logins get corrupted. Without this
 * code, core page caching breaks because the url path isn't set properly
 * for use as a cache id.
 *
 * Further, calling drupal_settings_initialize() will reset $conf to an array
 * which destroys caching settings.
 *
 * @see drupal_settings_initialize()
 *
 * @link http://drupal.org/node/1046844
 * @link http://drupal.org/node/1189916
 */
function domain_settings_initialize() {
  global $base_url, $base_path, $base_root, $cookie_domain;

  $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';

  if (isset($base_url)) {
    // Parse fixed base URL from settings.php.
    $parts = parse_url($base_url);
    $http_protocol = $parts['scheme'];
    if (!isset($parts['path'])) {
      $parts['path'] = '';
    }
    $base_path = $parts['path'] . '/';
    // Build $base_root (everything until first slash after "scheme://").
    $base_root = substr($base_url, 0, strlen($base_url) - strlen($parts['path']));
  }
  else {
    // Create base URL
    $http_protocol = $is_https ? 'https' : 'http';
    $base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
    $base_url = $base_root;

    // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
    // be modified by a visitor.
    if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
      $base_path = $dir;
      $base_url .= $base_path;
      $base_path .= '/';
    }
    else {
      $base_path = '/';
    }
  }
  $base_secure_url = str_replace('http://', 'https://', $base_url);
  $base_insecure_url = str_replace('https://', 'http://', $base_url);

  if ($cookie_domain) {
    // If the user specifies the cookie domain, also use it for session name.
    $session_name = $cookie_domain;
  }
  else {
    // Otherwise use $base_url as session name, without the protocol
    // to use the same session identifiers across http and https.
    list( , $session_name) = explode('://', $base_url, 2);
    // HTTP_HOST can be modified by a visitor, but we already sanitized it
    // in drupal_settings_initialize().
    if (!empty($_SERVER['HTTP_HOST'])) {
      $cookie_domain = $_SERVER['HTTP_HOST'];
      // Strip leading periods, www., and port numbers from cookie domain.
      $cookie_domain = ltrim($cookie_domain, '.');
      if (strpos($cookie_domain, 'www.') === 0) {
        $cookie_domain = substr($cookie_domain, 4);
      }
      $cookie_domain = explode(':', $cookie_domain);
      $cookie_domain = '.' . $cookie_domain[0];
    }
  }
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
  if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
    ini_set('session.cookie_domain', $cookie_domain);
  }
  // To prevent session cookies from being hijacked, a user can configure the
  // SSL version of their website to only transfer session cookies via SSL by
  // using PHP's session.cookie_secure setting. The browser will then use two
  // separate session cookies for the HTTPS and HTTP versions of the site. So we
  // must use different session identifiers for HTTPS and HTTP to prevent a
  // cookie collision.
  if ($is_https) {
    ini_set('session.cookie_secure', TRUE);
  }

  // We have set $cookie_domain, so we must match $session_name to it, since
  // that's what will happen inside drupal_settings_initialize() after we run.
  // Essentially, we short-circuit the IF routine when this copied code runs
  // after our routine.
  $session_name = $cookie_domain;

  // Now set the session token correctly.
  $prefix = ini_get('session.cookie_secure') ? 'SSESS' : 'SESS';
  session_name($prefix . substr(hash('sha256', $session_name), 0, 32));
}
