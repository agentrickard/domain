<?php

/**
 * @file
 * Interface between domain_source.module and views.module.
 *
 * @ingroup domain_source
 */

/**
 * Filter for domain ids stored in the database, currently active domain and any domain.
 */
class domain_source_handler_filter_domain_id extends domain_views_handler_filter_domain_domain_id {

  /**
   * Makes sure that views which filter by "Domain Source ID" == "Current Domain"
   * also include nodes which have their source domain set to
   * DOMAIN_SOURCE_USE_ACTIVE.
   *
   * @see http://drupal.org/node/1685658
   */
  function query() {
    if (in_array('***CURRENT_DOMAIN***', $this->value)) {
      $this->value[] = '***CURRENT_DOMAIN_SOURCE***';
    }
    return parent::query();
  }

}
