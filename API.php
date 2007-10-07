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
  * @mainpage
   Welcome to the API documentation for the Domain Access module, I hope you find it useful.
  
   If you find errors in the documentation, please file an issue at http://drupal.org/project/issues/domain.
  
   -- agentrickard
  */
  
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
 *    - path -- the link path (a Drupal-formatted path
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