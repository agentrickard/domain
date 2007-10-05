<?php
// $Id$

/**
 * @defgroup drupal Drupal core
 */

/**
 * @defgroup node Node hooks
 */

/**
 * @defgroup domain Domain Access module
 */

/**
 * @defgroup conf Domain Access configuration extension
 */
 
 /**
  * @defgroup hooks Domain API hooks
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