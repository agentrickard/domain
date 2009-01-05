<?php

// $Id$

/**
 * @file
 * Include file for settings.php to provide advanced
 * functionality for rewriting links as needed.
 *
 * @ingroup domain
 */

/**
 * Implements custom_url_rewrite_outbound().
 * Forces absolute paths for domains when needed.
 */
function custom_url_rewrite_outbound(&$path, &$options, $original_path) {
  global $_domain;
  
  // If the domain_id is not set, then the Domain module is not active, and we cannot run this function.
  if (!isset($_domain['domain_id'])) {
    return;
  }

  // Set static variables for the node lookups, to remove redundant queries.
  static $domain_site, $domain, $nodepaths, $path_rewrite;

  // This routine only needs to be run from certain urls or if we want to
  // force all links to go to a single domain for SEO.
  // See http://drupal.org/node/195366 for the background.
  $check = domain_grant_all();
  $seo = variable_get('domain_seo', 0);
  // If using Domain Source, we force links to a specific domain.
  $use_source = module_exists('domain_source');

  if ($check || $seo || $use_source) {
    // Check to see if this is a node or comment link and set $nid accordingly.
    // We static the $nid results to make this more efficient.
    $pattern = explode('/', $original_path);

    // Advanced pattern matching, we find the node id based on token %n in the path string.
    if (!isset($nodepaths)) {
      $pathdata = variable_get('domain_paths', "node/%n\r\nnode/%n/edit\r\ncomment/reply/%n\r\nnode/add/book/parent/%n\r\nbook/export/html/%n\r\nnode/%n/outline");
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
    $target_domain_id = $_domain['domain_id'];
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
          $target_domain_id = $domain[$nid]['domain_id'];
        }
      }
      // If strict SEO rules are enabled, we set "all affiliate" links to the root domain.
      // Only needed if we are not on the default source domain.
      else if ($root != -1 && $seo && $_domain['domain_id'] != $root['domain_id']) {
        $options['absolute'] = TRUE;
        // In this case, the $base_url cannot have a trailing slash
        $options['base_url'] = rtrim($root['path'], '/');
        $target_domain_id = $root['domain_id'];
      }
    }
    // We may have to implement hook_domainpath().
    if (!isset($path_rewrite)) {
      $path_rewrite = count(_domain_path_modules());
    }
    // Allow path changes, if needed.
    if ($path_rewrite > 0 && $path != '') {
      $path = domain_path($target_domain_id, $original_path, isset($options['language']) ? $options['language']->language : '');
    }
  }
}
