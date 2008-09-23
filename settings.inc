<?php
// $Id$
/**
 *
 * @file
 * settings.inc
 *
 * This file should be included at the bottom of your settings.php file:
 * <?php
 * include '/sites/all/modules/domain/settings.inc';
 * ?>
 * If you have installed the domain module into a different folder than
 * /sites/all/modules/domain please adjust the path approriately.
 *
 * @ingroup domain
 */

/**
 * Include bootstrap file, setup checker function and start bootstrap phases.
 */
include 'domain.bootstrap.inc';
domain_settings_setup_ok();
domain_bootstrap();

/**
 * Small helper function to determine whether this file was included correctly in
 * the user's settings.php
 *
 * If it was included at the right time then cache.inc shouldn't be included
 * yet and the function 'cache_get' not be defined.
 *
 * When the function is called first (from within this file) the state is saved
 * in a static variable, every later call will return that boolean value.
 *
 * @return
 * TRUE if settings.inc was included correctly in settings.php, else FALSE
 */
function domain_settings_setup_ok() {
  static $state = NULL;
  if ($state === NULL) {
    $state = !function_exists('cache_get');
  }
  return $state;
}

/**
 * Implements custom_url_rewrite_outbound().
 * Forces absolute paths for domains when needed.
 */
function custom_url_rewrite_outbound(&$path, &$options, $original_path) {
  global $_domain;

  // If the domain_id is not set, then the Domain module is not active, and we cannot run this function.
  if (isset($_domain['domain_id'])) {
    // Set static variables for the node lookups, to remove redundant queries.
    static $domain_site, $domain, $nodepaths;

    // Check to see that this function is installed.
    $skip = FALSE;
    $arg = arg(0);
    if ($arg == 'admin' && ($path == 'domain_access_test_path' || $path == 'domain_access_path_test')) {
      $path = 'yes';
      $skip = TRUE;
    }

    // This routine only needs to be run from certain urls or if we want to
    // force all links to go to a single domain for SEO.
    // See http://drupal.org/node/195366 for the background.
    $check = domain_grant_all();
    $seo = variable_get('domain_seo', 0);
    // If using Domain Source, we force links to a specific domain.
    $use_source =  module_exists('domain_source');

    if (!$skip && ($check || $seo || $use_source)) {
      // Check to see if this is a node or comment link and set $nid accordingly.
      // We static the $nid results to make this more efficient.
      $pattern = explode('/', $original_path);

      // Advanced pattern matching, we find the node id based on token %n in the path string.
      if (!isset($nodepaths)) {
        $pathdata = variable_get('domain_paths', "node/%n\r\nnode/%n/edit\r\ncomment/reply/%n\r\nnode/add/book/parent/%n\r\nbook/export/html/%n\r\nnode\%n\outline");
        $path_match = preg_replace('/(\r\n?|\n)/', '|', $pathdata);
        $nodepaths = explode("|", $path_match);
      }
      $nid = FALSE;
      foreach ($nodepaths as $match) {
        $match_array = explode('/', $match);
        $placeholder = array_search('%n', $match_array);
        if (isset($pattern[$placeholder])) {
          $match_array[$placeholder] = $pattern[$placeholder];
          if (is_numeric($pattern[$placeholder]) && $match_array == $pattern) {
            $nid = (int) $pattern[$placeholder];
            break;
          }
        }
      }
      // This path has matched a node id, so it may need to be rewritten.
      if ($nid) {
        $root = domain_lookup(variable_get('domain_default_source', 0));
        // Remove redundancy from the domain_site check.
        if (!isset($domain_site[$nid])) {
          // If this check works, we don't need to rewrite the path unless SEO rules demand it.
          $domain_site[$nid] = db_result(db_query("SELECT grant_view FROM {node_access} WHERE nid = %d AND gid = 0 AND realm = '%s'", $nid, 'domain_site'));
        }
        if (!$domain_site[$nid] || $use_source) {
          // Remove rendundancy from the domain_id check.
          if (!isset($domain[$nid])) {
            // The Domain Source module is optional, and allows nodes to be assigned to specific domains for the
            // purpose of this check.
            if ($use_source) {
              $source = db_result(db_query("SELECT domain_id FROM {domain_source} WHERE nid = %d", $nid));
              $domain[$nid] = domain_lookup($source);
            }
            else {
              // Load the domain data for this node -- but only take the first match.
              $id = db_result(db_query_range("SELECT gid FROM {node_access} WHERE nid = %d AND realm = '%s' AND grant_view = 1 ORDER BY gid", $nid, 'domain_id', 0, 1));
              $domain[$nid] = domain_lookup($id);
            }
          }
          // Can we and do we need to rewrite this path?
          if ($domain[$nid] != -1 && $domain[$nid]['domain_id'] != $_domain['domain_id']) {
            $options['absolute'] = TRUE;
            // In this case, the $base_url cannot have a trailing slash
            $options['base_url'] = rtrim($domain[$nid]['path'], '/');
            // Domain Source trumps the seo rules below.
            if (isset($source)) {
              $seo = FALSE;
            }
          }
        }
        // If strict SEO rules are enabled, we set "all affiliate" links to the root domain.
        // Only needed if we are not on the default source domain.
        else if ($root != -1 && $seo && $_domain['domain_id'] != $root['domain_id']) {
          $options['absolute'] = TRUE;
          // In this case, the $base_url cannot have a trailing slash
          $options['base_url'] = rtrim($root['path'], '/');
        }
      }
    }
  }
}
