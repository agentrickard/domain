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
 * @ingroup domain
 */

/**
 * Domain bootstrap phase 1: makes sure that database is initialised and
 * loads all necessary module files.
 */
define('DOMAIN_BOOTSTRAP_INIT', 0);

/**
 * Domain bootstrap phase 2: resolves host and does a lookup in the {domain}
 * table. Also invokes hook "hook_domain_bootstrap_lookup".
 */
define('DOMAIN_BOOTSTRAP_DOMAINNAME_RESOLVE', 1);

/**
 * Domain bootstrap phase 3: invokes bootstrap hook "hook_domain_bootstrap_full".
 */
define('DOMAIN_BOOTSTRAP_FULL', 2);

/**
 * Domain module bootstrap: calls all bootstrap phases.
 *
 * @todo
 * Change handling of bootstrap errors (atm it uses die() to send a msg
 * and exit the script; watchdog is not initialised yet and cannot be used.
 * Maybe php:trigger_error() is an option?
 */
function domain_bootstrap() {
  $phases = array(DOMAIN_BOOTSTRAP_INIT, DOMAIN_BOOTSTRAP_DOMAINNAME_RESOLVE, DOMAIN_BOOTSTRAP_FULL);
  foreach ($phases as $phase) {
    if (!_domain_bootstrap($phase)) {
      die('Domain Module: Error during domain_bootstrap(), Phase '.$phase);
    }
  }
}

/**
 * Calls individuell bootstrap phases.
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
    // make sure database is loaded
    _drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

    // load bootstrap modules
    _domain_bootstrap_modules_load();
    // if essential core module file has not been loaded, bootstrap fails.
    if (!function_exists('domain_load')) {
      return FALSE;
    }

    break;

  case DOMAIN_BOOTSTRAP_DOMAINNAME_RESOLVE:
    // get domain_id
    $_domain = domain_resolve_host();
    // if we don't have a valid domain id now, we can't really go on, bootstrap fails.
    if (empty($_domain) || !is_numeric($_domain['domain_id'])) {
      return FALSE;
    }
    break;

  case DOMAIN_BOOTSTRAP_FULL:
    _domain_bootstrap_invoke_all('full', $_domain);
    break;
  }
  return TRUE;
}

/**
 * Registers a module so it is loaded during domain_bootstrap and invoked
 * domain_bootstrap hooks on.
 *
 * This function should be called from within hook_enable() implementations.
 *
 * @param $name
 * The name of the module that is registered.
 * @param $weight
 * The weight of the module as an integer number. The default value is the
 * respective value from the {system} table. Optional.
 */
function domain_bootstrap_register($name, $weight = NULL) {
  // load old list of modules
  $modules = _domain_bootstrap_modules_get();

  // if $weight is not an integer load the weight from the {system} table.
  if (!is_integer($weight)) {
    $weight = db_result(db_query("SELECT weight FROM {system} WHERE name = '%s'", $name));
  }

  // update/add the module and its weight:
  $modules[$weight.'-'.$name] = $name;

  // and store the new list of modules.
  _domain_bootstrap_modules_set($modules);
}

/**
 * Removes a module so it is not loaded during domain_bootstrap anymore.
 *
 * This function should be called from within hook_disable() implementations.
 *
 * @param $name
 * The name of the module that is un-registered.
 */
function domain_bootstrap_unregister($name) {
  $modules = _domain_bootstrap_modules_get();
  if (is_array($modules)) {
    foreach($modules as $k => $v){
      if ($v == $name) {
        unset($modules[$k]);
      }
    }
  }
  _domain_bootstrap_modules_set($modules);
}

/**
 * Returns a list of modules which are loaded during domain_bootstrap phases and
 * called respective hooks on.
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
function domain_bootstrap_modules($reset = FALSE) {
  static $modules = NULL;

  // if parameter is not given but $bootstrap_modules empty load modules from db
  if ($reset || is_null($modules)) {
    $modules = _domain_bootstrap_modules_get();
    if (!is_array($modules)) {
      $modules = array();
    }
    if (!in_array('domain', $modules)) {
      $modules['-99-domain'] = 'domain';
    }
    ksort($modules);
  }

  return $modules;
}

/**
 * Tries to load all domain bootstrap modules (see _domain_bootstrap_modules()).
 */
function _domain_bootstrap_modules_load() {
  $modules = domain_bootstrap_modules();

  foreach ($modules as $module) {
    drupal_load('module', $module);
  }
}

/**
 * Retrieves the value of the variable 'domain_bootstrap_modules' from the
 * {variable} table. This function does not use Drupal's variable system.
 *
 * @return
 * An array containing module names. (The keys are combined from module weight
 * and module name and used for sorting during domain_bootstrap_modules().)
 */
function _domain_bootstrap_modules_get() {
  $key = 'domain_bootstrap_modules';
  $conf[$key] = unserialize(db_result(db_query("SELECT value FROM {variable} WHERE name = '%s'", $key)));
  return $conf[$key];
}

/**
 * Set variable 'domain_bootstrap_modules' to given value.
 *
 * This function does not use drupal's variable system calls because they are not
 * yet available in all cases.
 *
 * @param $modules
 * An array containing module names. The keys should be of the format
 * {weight}-{module-name} to allow easy sorting by module weight later.
 */
function _domain_bootstrap_modules_set($modules = NULL) {
  $key = 'domain_bootstrap_modules';

  if (!is_array($modules)) {
    $modules = array();
  }

  $serialized_value = serialize($modules);
  db_query("UPDATE {variable} SET value = '%s' WHERE name = '%s'", $serialized_value, $key);
  if (!db_affected_rows()) {
    db_query("INSERT INTO {variable} (name, value) VALUES ('%s', '%s')", $key, $serialized_value);
  }

  $conf[$key] = $modules;
}

/**
 * Removes all disabled or not installed modules from the
 * 'domain_bootstrap_modules' variable.
 */
function domain_bootstrap_modules_cleanup() {
  $modules = _domain_bootstrap_modules_get();
  foreach($modules as $k => $name) {
    $status = db_result(db_query("SELECT status FROM {system} WHERE name = '%s'", $name));
    if (!$status) {
      unset($modules[$k]);
    }
  }
  _domain_bootstrap_modules_set($modules);
}

/**
 * Tries to call specified hook on all domain_bootstrap modules.
 *
 * The hook function names are of the following format:
 * {$module}_domain_bootstrap_{$hook}
 * where {$module} is the name of the module implementing the hook and {$hook}
 * is the identifier for the concrete domain bootstrap hook.
 *
 * This function is basically a copy of module_invoke_all() adjusted to our
 * needs.
 *
 * @param $hook
 * The name of the bootstrap hook to invoke.
 *
 * @link http://api.drupal.org/api/function/module_invoke_all/6
 */
function _domain_bootstrap_invoke_all() {
  $args = func_get_args();
  $hook = $args[0];
  unset($args[0]);
  $return = array();
  foreach (domain_bootstrap_modules() as $module) {
    $function = $module . '_domain_bootstrap_' . $hook;
    if (function_exists($function)) {
      $result = call_user_func_array($function, $args);
      if (isset($result) && is_array($result)) {
        $return = array_merge_recursive($return, $result);
      } else if (isset($result)) {
        $return[] = $result;
      }
    }
  }

  return $return;
}

/**
 * Tries to match the current (host) domainname to a domain in the {domain}
 * table and returns a respective domain_id.
 *
 * @param $_domainname
 * The domainname to match against. Optional.
 *
 * @return
 * An array containing a domain_id matching the current domainname.
 */
function domain_resolve_host($_domainname = "") {
  if (empty($_domainname)) {
    $_domainname = domain_current_domainname();
  }

  return _domain_lookup_simple($_domainname);
}

/**
 * Determines current fully qualified domainname.
 *
 * @return
 * The current (host) domainname as a String.
 */
function domain_current_domainname() {
  // We lower case this, since EXAMPLE.com == example.com.
  return strtolower(rtrim($_SERVER['HTTP_HOST']));
}

/**
 * Determines a domain_id matching given $_domainname.
 *
 * This function runs a lookup against the {domain} table matching the
 * subdomain column against the given parameter $_domainname. If a match is
 * found the function returns an array containing the subdomain (= $_domainname)
 * and the matching domain_id from the {domain} table.
 *
 * If no match is found domain_id is set to 0 for the default domain.
 *
 * During the process hook_domain_bootstrap_lookup() is invoked to allow other
 * modules to modify that result.
 *
 * @param $domainname
 * The string representation of a {domain} entry.
 *
 * @param $reset
 * Set TRUE to ignore cached versions and look the name up again. Optional.
 *
 * @return
 * An array containing a domain_id from {domain} matching the given domainname
 */
function _domain_lookup_simple($_domainname, $reset = false) {
  static $cache = array();

  if (empty($_domainname)) return 0;

  if ($reset || !isset($cache[$_domainname])) {
    // Lookup the given domainname against our allowed hosts record.
    $domain = db_fetch_array(db_query_range("SELECT domain_id FROM {domain} WHERE subdomain = '%s' ", $_domainname, 0, 1));

    if (!is_array($domain)) {
      $domain = array();
    }

    $domain['subdomain'] = $_domainname;
    // invoke hook_domain_bootstrap_lookup()
    $domain_new = _domain_bootstrap_invoke_all('lookup', $domain);
    if (is_array($domain_new)) {
      $domain = array_merge($domain, $domain_new);
    }
    // no match => use default (0)
    if (!isset($domain['domain_id'])) {
      $domain['domain_id'] = 0;
    }

    $cache[$_domainname] = $domain;
  }
  return $cache[$_domainname];
}
