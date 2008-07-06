<?php
// $Id$

/**
 * @file
 * Provides the views data and handlers for domain.module.
 *
 * Original code by mrichar1.  Port to Drupal 6 by nonsie.
 *
 * @ingroup domain_views
 */

/**
 * Implementation of hook_views_data().
 *
 */
function domain_views_data() {
  // ----------------------------------------------------------------
  // domain_access - access table for anything domain related
  $data['domain_access']['table']['group']  = t('Domain Access');
  $data['domain_access']['table']['join'] = array(
    'node' => array(
    'left_field' => 'nid',
    'field' => 'nid',
    'extra' => array(
      array('field' => 'realm', 'value' => 'domain_id', 'operator' => '=', 'numeric' => FALSE),
      ),
    ),
  );

  // ----------------------------------------------------------------
  // domain - domain specific data
  $data['domain']['table']['group']  = t('Domain');
  $data['domain']['table']['join'] = array(
    'node' => array(
    'left_table' => 'domain_access',
    'left_field' => 'gid',
    'field' => 'domain_id',
    'extra' => array(
      array('field' => 'valid', 'value' => '1', 'operator' => '=', 'numeric' => FALSE),
      ),
    ),
  );


  // ----------------------------------------------------------------
  // domain_access.gid
  $data['domain_access']['gid'] = array(
  'title' => t('Domain'),
  'help' => t('Domain ID the node belongs to.'),
  'field' => array(
    'handler' => 'views_handler_field',
    'click sortable' => TRUE,
    ),
  'sort' => array(
    'handler' => 'views_handler_sort',
    ),
  'filter' => array(
    'name' => t('Domain Access: Domain'),
    'handler' => 'domain_views_handler_filter_domain_id',
    'help' => t('This will filter a view to only show nodes from the specified domain.'),
    ),
  'argument' => array(
    'name' => t('Domain Access: Domain'),
    'handler' => 'domain_views_handler_argument_domain_id', // This is the function that handles all the work
    'help' => t('The argument will filter a view to only show nodes from the specified domain.'),
  ) ,
  );
  return $data;
}

/**
 * Filter handler to accept a domain ID.
 */
class domain_views_handler_filter_domain_id extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Domain');
      $domain_options = array('-1' => t('Any domain -- use with "Is not one of"'), '***CURRENT_DOMAIN***' => t('Current Domain'));
      $domains = domain_domains();
      foreach ($domains as $domain) {
        $domain_options[$domain['domain_id']] = $domain['sitename'];
        }
      $this->value_options = $domain_options;
    }
  }
}

/**
 * Implementation of hook_views_query_substitutions ()
 */
function domain_views_query_substitutions($view) {
  return array('***CURRENT_DOMAIN***' => $GLOBALS['_domain']['domain_id']);
}

/**
 * This is the function that handles all the work for dealing with view arguments based on domain ID
 */
class domain_views_handler_argument_domain_id extends views_handler_argument {
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
    return isset($domain_options[$domain_id]) ? $domain_options[$domain_id] : t('Unknown domain');
  }
}
