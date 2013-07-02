<?php

/**
 * @file
 * API documentation file for Domain module.
 */

/**
 * Standard classes and implementations.
 */

/**
 * Notifies other modules that we are loading a domain record from the database.
 *
 * When using this hook, you should invoke the namespace with:
 *
 * use Drupal\domain\DomainInterface;
 *
 *
 * @param array $domain
 *   An array of $domain record objects.
 *
 */
function hook_domain_load(array $domain) {
  // Add a variable to the $domain.
  foreach ($domains as $domain) {
    $domain->myvar = 'mydomainvar';
    // Modify the site_grant flag, removing access to 'all affiliates.'
    $domain->site_grant = FALSE;
  }
}

/**
 * Adds administrative operations for the domain overview form.
 *
 * @param &$operations
 *  An array of links, which uses a unique string key and requires the
 *  elements 'title' and 'href'; the 'query' value is optional, and used
 *  for link-actions with tokens.
 */
function hook_domain_operations(&$operations) {
  // Add aliases to the list.
  $operations['domain_alias'] = array(
    'title' => t('alias'),
    'href' => "admin/structure/domain/$domain->machine_name/alias",
    'query' => array(),
  );
}
