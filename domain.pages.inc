<?php

/**
 * @file
 * Administrative pages for the Domain module.
 */

use Drupal\domain\DomainInterface;

/**
 * Lists the domains active on the site.
 */
function domain_page() {
  // @TODO access filtering.
  $domains = domain_load_multiple();
  $build['domains'] = domain_view_multiple($domains);

  return $build;
}

/**
 * Views a domain record as a non-administrator.
 *
 * @param Drupal\domain\Plugin\Core\Entity\DomainInterface $domain
 *   A domain entity.
 */
function domain_page_view(DomainInterface $domain) {
  return domain_view($domain);
}
