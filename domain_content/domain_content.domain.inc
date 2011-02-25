<?php

/**
 * @file
 * Domain hooks for Domain Content.
 *
 * @ingroup domain_content
 */

/**
 * Implements hook_domain_link()
    DEPRECATED
function domain_content_domain_link($domain) {
  $links[] = array(
    'title' => t('content'),
    'path' => 'admin/domain/content/'. $domain['domain_id']
  );
  return $links;
}
*/
