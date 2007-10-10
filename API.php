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
 * @defgroup list Domain List: navigation block
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
 * Notify other modules that we have created a new domain or 
 * updated a domain record.  Where possible, use the $domain values
 * in preference to the $edit values.
 *
 * @param $op
 *  The operation being performed: 'create', 'update', 'delete'
 * @param $edit
 *  The form values processed by the form.
 *
 * @ingroup hooks
 */
function hook_domainrecord($op, $domain = array(), $edit = array()) {
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