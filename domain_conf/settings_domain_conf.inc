<?php
// $Id$

/**
 * @file
 * Dynamic domain settings loading.
 *
 * Loads the settings for the current domain.
 *
 * This routine was in hook_init(), but there are cases where
 * the $conf array needs to be loaded in early phases of bootstrap.
 * In particular, these variables need to be available during variable_init().
 *
 * In order for this to work, we must leap ahead in the boostrap process.
 * This step ensures that the database functions are active.
 * Then we run our function, overriding the default $conf variables, as
 * indicated in settings.php
 *
 * @ingroup domain_conf
 */

_drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
_domain_conf_load();

/**
 * Load the varaibles for this subdomain
 *
 * @ingroup conf
 */
function _domain_conf_load() {
  $check = db_result(db_query("SELECT status FROM {system} WHERE name = 'domain_conf'"));
  if ($check > 0) {
    // Cribbed from bootstrap.inc -- removes port protocols from the host value.
    $_subdomain = strtolower(implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    // Lookup the active domain against our allowed hosts record.
    $domain = db_fetch_array(db_query("SELECT domain_id FROM {domain} WHERE subdomain = '%s'", $_subdomain));
    // If nothing was found, use the default domain.
    if (!isset($domain['domain_id'])) {
      $domain['domain_id'] = 0;
    }
    $data = array();
    $data = db_fetch_array(db_query("SELECT settings FROM {domain_conf} WHERE domain_id = %d", $domain['domain_id']));
    if (!empty($data)) {
      global $conf;
      $settings = unserialize($data['settings']);
      // Overwrite the $conf variables.
      foreach ($settings as $key => $value) {
        $conf[$key] = $value;
      }
    }
  }
}
