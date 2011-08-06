<?php


/**
 * @file
 * Include file for settings.php to provide advanced
 * functionality for rewriting links as needed.
 *
 * @ingroup domain
 */

/**
 * Implements hook_url_outbound_alter().
 *
 * Forces absolute paths for domains when needed.
 */
function domain_url_outbound_alter(&$path, &$options, $original_path) {
  $_domain = domain_get_domain();

  // If the domain_id is not set, then the Domain module is not active, and we cannot run this function.
  if (!isset($_domain['domain_id'])) {
    return;
  }

  // Set static variables for the node lookups, to remove redundant queries.
  static $domain_site, $domain, $nodepaths, $path_rewrite, $use_source, $root, $check_nodes, $path_lookup, $path_alter;

  // This routine only needs to be run from certain urls or if we want to
  // force all links to go to a single domain for SEO.
  // See http://drupal.org/node/195366 for the background.

  // These two functions are statically cached elsewhere.
  // Are we viewing nodes from all sites?
  $check = domain_grant_all();
  // Are we forcing path rewrites?
  $seo = variable_get('domain_seo', 0);
  // Check for source rewrites.
  if (!isset($use_source)) {
    $use_source = (bool) count(module_implements('domain_source_alter'));
  }
  // Querying for nodepaths is expensive, so only do it if needed.
  if (!isset($check_nodes)) {
    $check_nodes = $check || $seo || $use_source;
  }
  // Check for path rewrites.
  if (!isset($path_alter)) {
    $path_alter = (bool) count(module_implements('domain_source_path_alter'));
  }
  // Get the root url.
  if (!isset($root)) {
    $root = domain_lookup(variable_get('domain_default_source', 0));
  }
  // Set the target domain for this url.
  $target_domain_id = $_domain['domain_id'];
  // Now run the path rewrite sequence, if necessary.
  if ($check_nodes || $path_alter) {
    $nid = FALSE;
    if ($check_nodes) {
      // Check to see if this is a node or comment link and set $nid accordingly.
      // We static the $nid results to make this more efficient.
      $pattern = explode('/', $original_path);

      // Advanced pattern matching, we find the node id based on token %n in the path string.
      if (!isset($nodepaths)) {
        $pathdata = variable_get('domain_paths', "node/%n\r\nnode/%n/edit\r\ncomment/reply/%n\r\nnode/add/book/parent/%n\r\nbook/export/html/%n\r\nnode/%n/outline");
        $path_match = preg_replace('/(\r\n?|\n)/', '|', $pathdata);
        $nodepaths = explode("|", $path_match);
      }
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
    }
    // This path has matched a node id, so it may need to be rewritten.
    if (!empty($nid)) {
      // Remove redundancy from the domain_site check.
      if (!isset($domain_site[$nid])) {
        // If this check works, we don't need to rewrite the path unless SEO rules demand it.
        $domain_site[$nid] = db_query("SELECT COUNT(nid) FROM {domain_access} WHERE nid = :nid AND gid = 0 AND realm = :realm", array(':nid' => $nid, ':realm' => 'domain_site'))->fetchField();
      }
      if (!$domain_site[$nid] || $use_source) {
        // Remove redundancy from the domain_id check.
        if (!isset($domain[$nid])) {
          $domain[$nid] = domain_get_node_match($nid);
        }
        // Can we and do we need to rewrite this path?
        if ($domain[$nid] != -1 && $domain[$nid]['domain_id'] != $_domain['domain_id']) {
          $options['absolute'] = TRUE;
          // In this case, the $base_url cannot have a trailing slash
          $options['base_url'] = rtrim($domain[$nid]['path'], '/');
          // Domain Source trumps the seo rules below.
          if ($use_source) {
            $seo = FALSE;
          }
          $target_domain_id = $domain[$nid]['domain_id'];
        }
      }
      // If strict SEO rules are enabled, we set "all affiliate" links to the root domain.
      // Only needed if we are not on the default source domain.
      elseif ($root != -1 && $seo && $_domain['domain_id'] != $root['domain_id']) {
        $options['absolute'] = TRUE;
        // In this case, the $base_url cannot have a trailing slash
        $options['base_url'] = rtrim($root['path'], '/');
        $target_domain_id = $root['domain_id'];
      }
    }
    // Hook for non-node paths.
    elseif (!empty($path_alter)) {
      // Must use md5 here, to prevent array keys from breaking.
      $name = md5($path);
      if (!isset($path_lookup[$name])) {
        $path_lookup[$name] = domain_get_path_match($path);
      }
      if ($path_lookup[$name] != $_domain) {
        $options['absolute'] = TRUE;
        // In this case, the $base_url cannot have a trailing slash
        $options['base_url'] = rtrim($path_lookup[$name]['path'], '/');
        $target_domain_id = $path_lookup[$name]['domain_id'];
        // TODO: merge this code with the above for cleanup.
      }
    }
  }
  // We may have to refactor this for D7.
  if (!isset($path_rewrite)) {
    $path_rewrite = count(_domain_path_modules());
  }
  // Allow path changes, if needed. This changed significantly in D7.
  if ($path_rewrite > 0 && $path != '') {
    domain_path($target_domain_id, $path, $options, $original_path);
  }
}
