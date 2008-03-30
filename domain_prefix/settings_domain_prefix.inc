<?php
// $Id$

/**
 * @file
 * Dynamic domain table prefix loading.
 *
 * Loads the table prefixes for the current domain.
 */

_drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
_domain_prefix_load();

/**
 * Load the prefixes for this subdomain
 *
 * @ingroup prefix
 */
function _domain_prefix_load() {
  $check = db_result(db_query("SELECT status FROM {system} WHERE name = '%s'", 'domain_prefix'));
  if ($check) {
    // Cribbed from bootstrap.inc -- removes port protocols from the host value.
    $_subdomain = strtolower(implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    // Lookup the active domain against our allowed hosts record.
    $domain = db_fetch_array(db_query("SELECT domain_id FROM {domain} WHERE subdomain = '%s'", $_subdomain));
    if (isset($domain['domain_id'])) {
      $tables = array();
      $prefix = 'domain_'. $domain['domain_id'] .'_';
      $result = db_query("SELECT tablename FROM {domain_prefix} WHERE domain_id = %d AND status > %d", $domain['domain_id'], 1);
      while ($data = db_fetch_array($result)) {
        $tables[] = $data['tablename'];
      }
      if (!empty($tables)) {
        global $db_prefix;
        $new_prefix = array();
        // There might be global prefixing; if so, prepend the global.
        if (is_string($db_prefix)) {
          $new_prefix['default'] = $db_prefix;
          $prefix = $db_prefix . $prefix;
        }
        foreach ($tables as $table) {
          $new_prefix[$table] = $prefix;
        }
        $db_prefix = $new_prefix;
      }
    }
  }
}
