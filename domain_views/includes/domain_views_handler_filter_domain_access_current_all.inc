<?php

/**
 * @file
 * Interface between domain_views.module and views.module.
 *
 * @ingroup domain_views
 */

/**
 * Filter out nodes published to all domains or current domain
 */
class domain_views_handler_filter_domain_access_current_all extends views_handler_filter_boolean_operator  {
  function query() {
    $table = $this->ensure_my_table();
    if (empty($this->value)) {
      $this->query->add_where($this->options['group'], "(($table.realm = 'domain_id' AND $table.$this->real_field ". '<>' ." ***CURRENT_DOMAIN***))");
    }
    else {
      $this->query->add_where($this->options['group'], "(($table.realm = 'domain_id' AND $table.$this->real_field ". '=' ." ***CURRENT_DOMAIN***) OR ($table.realm = 'domain_site' AND $table.$this->real_field = 0))");
    }
  }
}
