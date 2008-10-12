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
 * Implements hook_views_data().
 */
function domain_views_views_data() {
  $data = array();
  // Define the {domain} table.
  $data['domain']['table']['group'] = t('Domain');
  // We can use this as a base table for Views.
  $data['domain']['table']['base'] = array(
    'field' => 'domain_id',
    'title' => t('Domain'),
    'help' => t('Domain Access uses domains to divide and display content on a group of related sites.'),
  );
  // Define the joins.
  $data['domain']['table']['join']['node'] = array(
    'left_table' => 'domain_access',
    'left_field' => 'gid',
    'field' => 'domain_id',
    'type' => 'INNER',
    'extra' => "domain_access.realm = 'domain_id'",
  );
  // Define the fields.
  $data['domain']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('The unique id key for the active domain.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['domain']['subdomain'] = array(
    'title' => t('Domain'),
    'help' => t('The URL string that identifies a domain.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['domain']['sitename'] = array(
    'title' => t('Site name'),
    'help' => t('The display string that identifies a domain.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['domain']['scheme'] = array(
    'title' => t('Scheme'),
    'help' => t('The http protocol used by the domain.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['domain']['valid'] = array(
    'title' => t('Valid'),
    'help' => t('A boolean flag indicating the domain can be seen by site visitors.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Valid'),
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );  

  // Define the {domain_access} table.
  $data['domain_access']['table']['group'] = t('Domain');
  // Define the joins.
  $data['domain_access']['table']['join']['node'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
  );
  // Define the fields.
  $data['domain_access']['nid'] = array(
    'title' => t('Node ID'),
    'help' => t('The node id.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'title' => t('Domain node'),
      'help' => t("Bring in information about the domain this node is assigned to."),
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Domain node'),
    ),
  );
  // Define the fields.
  $data['domain_access']['gid'] = array(
    'title' => t('Group ID'),
    'help' => t('The group id of the record.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  // Define the fields.
  $data['domain_access']['realm'] = array(
    'title' => t('Access Type'),
    'help' => t('The domain realm.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  return $data;
}

/**
 * Implementation of hook_views_handlers() to register all of the basic handlers
 * views uses.

function domain_views_views_handlers() {
  $handlers = array();
  $handlers['handlers'] = array(
    // field handlers
    'views_handler_argument_domain_id' => array(
      'parent' => 'views_handler_argument_numeric',
    ),
  );
  return $handklers;
}
 */
