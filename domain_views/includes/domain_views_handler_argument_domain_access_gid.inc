<?php

/**
 * @file
 * Interface between domain_views.module and views.module.
 *
 * @ingroup domain_views
 */

/**
 * Argument for domain ids stored in the database currently active domain and any domain.
 */
class domain_views_handler_argument_domain_access_gid extends views_handler_argument  {
 // If you pass "current" as the argument, it filters according to the domain the page is being viewed from
  function set_argument($arg) {
    if ($arg == 'current') {
      $arg = $GLOBALS['_domain']['domain_id'];
    }
    return parent::set_argument($arg);
  }

  function title() {
    return $this->domain_id($this->argument);
  }

  function summary_name($data) {
    return $this->domain_id($data->{$this->name_alias});
  }

  function summary_argument($data) {
    return $this->domain_id($data->{$this->name_alias});
  }

  function domain_id($domain_id) {
    $domains = domain_domains();
    foreach ($domains as $domain) {
      $domain_options[$domain['domain_id']] = $domain['sitename'];
    }
    return isset($domain_options[$domain_id]) ? check_plain($domain_options[$domain_id]) : t('Unknown domain');
  }
}
