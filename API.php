<?php
// $Id$

/**
 * @file
 * API documentation file.
 */

/**
 * @defgroup drupal Drupal core
 * Core Drupal hooks.
 */

/**
 * @defgroup node Node hooks
 * Drupal node and node_access hooks.
 */

/**
 * @defgroup domain Domain Access module
 * Core functions for the Domain Access module.
 */

/**
 * @defgroup conf Domain Conf: configuration extension
 * Core functions for the Domain Conf module.
 */
 
 /**
  * @defgroup hooks Domain API hooks
  * Internal module hooks for Domain Access.
  */

/**
 * @defgroup nav Domain Navigation: navigation block and menu options
 * Configurable navigation and block based on active domains.
 */

/**
 * @defgroup content Domain Content : administer nodes
 * Configurable navigation block based on active domains.
 */

/**
 * @defgroup themes Theme functions
 * Theme functions used by the Domain Access modules.
 */

 /**
  * @mainpage
   Welcome to the API documentation for the Domain Access module, I hope you find it useful.
  
   If you find errors in the documentation, please file an issue at http://drupal.org/project/issues/domain.
  
   -- agentrickard
  */

/**
 * Notify other modules that we are granting access to a node.
 *
 * This hook allows Domain Access modules to overwrite default behaviors.
 * See http://api.drupal.org/api/function/hook_node_grants/5 for more detail.
 * 
 * @param &$grants
 *  The existing default $grants, passed by reference.
 * @param $account
 *  The user object of the user requesting the node.
 * @param $op
 *  The node operation being performed (view, update, or delete).
 *
 * @return
 *  No return value. Modify the $grants array, passed by reference.
 *
 * @ingroup hooks
 */
function hook_domaingrants(&$grants, $account, $op) {
  // Add a sample grant privilege to let a user see their content at all times.
  $grants['domain_example'][] = $account->uid;
  return $grants;
}


/**
 * Notify other modules that we are saving node access records.
 *
 * This hook allows Domain Access modules to overwrite the default bahaviors.
 * See http://api.drupal.org/api/function/hook_node_access_records/5 for more detail.
 * 
 * @param &$grants
 *  The existing default $grants, passed by reference.
 * @param $node
 *  The node object being saved.
 *
 * @return
 *  No return value. Modify the $grants array, passed by reference.
 * 
 *
 * @ingroup hooks
 */
function hook_domainrecords(&$grants, $node) {
  // Add a sample access record to let a user see their content at all times.
  $grants[] = array(
    'realm' => 'domain_example',
    'gid' => $node->uid,
    'grant_view' => TRUE,
    'grant_update' => TRUE,
    'grant_delete' => TRUE,
    'priority' => 0,         // If this value is > 0, then other grants will not be recorded
  );
  return $grants;
}

/**
 * Notifies other modules that we are loading a domain record from the database.
 *
 * Modules may overwrite or add to the $domain array for each subdomain.
 *
 * WARNING: If you need to make revisions to the $_domain global before it is processed
 * by other modules, you must implement hook_init().  Only modules that implement hook_init()
 * are loaded during the creation routine for the $_domain global.  If your module has not
 * been loaded, then hook_domainload() will skip your implementation.
 *
 * When loading lists of domains or generating domain information, either use the proper
 * functions -- domain_default(), domain_lookup(), and domain_domains() -- or invoke this hook.
 *
 * Invoked by domain_lookup() and domain_default().
 *
 * @param &$domain
 *  The current $domain array.
 *
 * @return
 *  The modified $domain array.
 *
 * @ingroup hooks
 */
function hook_domainload($domain) {
  // Add a variable to the $domain array.
  $domain['myvar'] = 'mydomainvar';
  // Remove the site_grant flag, making it so users can't see content for 'all affiliates.'
  $domain['site_grant'] = FALSE;
}
  
/**
 * Notify other modules that we have created a new domain or 
 * updated a domain record.  
 *
 * NOTE: Where possible, use the $domain values in preference to the $edit values.
 *
 * @param $op
 *  The operation being performed: 'create', 'update', 'delete'
 * @param $edit
 *  The form values processed by the form.
 *
 * @ingroup hooks
 */
function hook_domainupdate($op, $domain = array(), $edit = array()) {
  switch ($op) {
    case 'create':
      db_query("INSERT INTO {mytable} (subdomain, sitename) VALUES ('%s', '%s')", $domain['subdomain'], $domain['sitename']);
      break;
    case 'update':
      db_query("UPDATE {mytable} SET subdomain = '%s', sitename = '%s' WHERE domain_id = %d", $domain['subdomain'], $domain['sitename'], $domain['domain_id']);
      break;
    case 'delete':
      db_query("DELETE FROM {mytable} WHERE subdomain = '%s'", $domain['subdomain']);
      break;
  }
}

/**
 * Returns links to additional functions for the Domain Access module's admin screen
 *
 * @param $domain
 *  An array of data for the active domain, taken from the {domain} table.
 *    - domain_id -- the unique identifier of this domain
 *    - subdomain -- the host path of the url for this domain
 *    - sitename -- the human-readable name of this domain
 *
 * @return
 *  An array of links to append to the admin screen, in the format:
 *    - title -- the link title
 *    - path -- the link path (a Drupal-formatted path)
 *  The data returned by this function will be passed through the l() function.
 *
 * @ingroup hooks
 */
function hook_domainlinks($domain) {
  $links[] = array(
    'title' => t('settings'),
    'path' => 'admin/build/domain/conf/'. $domain['domain_id']
  );
  return $links;
}  

/**
 * Enables modules to add additional parameters to the $domain array
 * for use by the Domain Navigation module.
 *
 * Used in cases where custom themes may require extra parameters.
 * This hook is called by domain_nav_render().
 *
 * Default parameters should not be changed; these are:
 *
 *    - domain_id -- the unique identifier of this domain
 *    - subdomain -- the host path of the url for this domain
 *    - sitename -- the human-readable name of this domain
 *    - path -- the link path (a Drupal-formatted path)
 *    - active -- a boolean flag indicating the currently active domain
 *
 * @ingroup hooks
 */
function hook_domainnav($domain) {
  $extra = array();
  $extra['test'] = 'test';
  return $extra;
}

/**
 * Enables Domain Access modules to fire cron hooks across all
 * active domains.  
 *
 * Each module implementing this hook will have the function run
 * once per active domain record.  The global $_domain variable 
 * will be set to the current $domain passed as an argument.
 *
 * This function is especially useful if you need to run node queries
 * that obey node access rules.
 *
 * @param $domain
 *  The information for the current domain record, taken from {domain}.
 *
 * @ingroup hooks
 */
function hook_domaincron($domain) {
  // Run a node query.
  $result = db_query_range(db_rewrite_sql("SELECT n.nid FROM {node} n ORDER BY n.changed"), 0, 1);
  $node = db_fetch_object($result);
  // Set a variable for each domain containing the last node updated.
  variable_set('domain_'. $domain['domain_id'] .'_lastnode', $node->nid);
} 