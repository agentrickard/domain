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
 * use Drupal\domain\Plugin\Core\Entity\Domain;
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
