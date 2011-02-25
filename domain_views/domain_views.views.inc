<?php

/**
 * @file
 * Provides the views data and handlers for domain.module.
 *
 * Original code by mrichar1.  Port to Drupal 6 by nonsie.
 *
 * @ingroup domain_views
 */

/**
 * Implement hook_views_data().
 */
function domain_views_views_data() {
  $data = array();
  // Define the {domain} table.
  $data['domain']['table']['group'] = t('Domains');
  // We can use this as a base table for Views.
  $data['domain']['table']['base'] = array(
    'field' => 'domain_id',
    'title' => t('Domains'),
    'help' => t('Domain Access uses domains to divide and display content on a group of related sites.'),
  );
  //Define the joins.
  $data['domain']['table']['join']['node'] = array(
    'left_table' => 'domain_access',
    'left_field' => 'gid',
    'field' => 'domain_id',
  );
  //Define the joins between {node_revision} and {domain}.
  $data['domain']['table']['join']['node_revisions'] = array(
    'left_table' => 'domain_access',
    'left_field' => 'gid',
    'field' => 'domain_id',
  );
  // Define the fields.
  $data['domain']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('The unique id key for the active domain.'),
    'field' => array(
      'click sortable' => TRUE,
      'handler' => 'domain_views_handler_field_domain_id',
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'domain_views_handler_filter_domain_domain_id',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['domain']['subdomain'] = array(
    'title' => t('Subdomain'),
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
      'handler' => 'domain_views_handler_field_domain_sitename',
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
  $data['domain_access']['table']['group'] = t('Domain Access');
  // Define the joins.
  $data['domain_access']['table']['join']['node'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
  );
  // Define the join between {node_revisions} and {domain_access} table
  $data['domain_access']['table']['join']['node_revisions'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
  );
  $data['domain_access']['table']['join']['domain'] = array(
    'left_field' => 'domain_id',
    'field' => 'gid',
    'extra' => array(
      array('field' => 'realm', 'value' => 'domain_id', 'numeric' => FALSE),
    ),
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
    'title' => t('Domain ID'),
    'help' => t('The domain id of the record.'),
    'field' => array(
      'click sortable' => TRUE,
      'handler' => 'domain_views_handler_field_domain_id',
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'domain_views_handler_filter_domain_access_gid',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information accepting domain_id as an argument
    'argument' => array(
      'handler' => 'domain_views_handler_argument_domain_access_gid',
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
   // Define available on current domain field
  $data['domain_access']['current_all'] = array(
    'title' => t('Available on current domain'),
    'help' => t('Filters out nodes not available on current domain (published to current domain or all affiliates'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'field' => 'gid',
      'handler' => 'domain_views_handler_filter_domain_access_current_all',
      'label' => t('Available on current domain'),
    ),
  );
  // Define the {domain_editor} table.
  $data['domain_editor']['table']['group'] = t('Domain Editor');
  // Define the joins.
  $data['domain_editor']['table']['join']['users'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );
  $data['domain_editor']['table']['join']['node'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );
  // Define the join between {node_revisions} and {domain_editor} table
  $data['domain_editor']['table']['join']['node_revisions'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );
  // Define the fields.
  $data['domain_editor']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('The user\'s id.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  // Define the fields.
  $data['domain_editor']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('The domain(s) an editor is assigned to.'),
    'field' => array(
      'click sortable' => TRUE,
      'handler' => 'domain_views_handler_field_domain_id',
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'domain_views_handler_filter_domain_domain_id',
    ),
    // Information for accepting a domain_id as a filter
    'argument' => array(
      'handler' => 'domain_views_handler_argument_domain_access_gid',
    ),
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'title' => t('Domain editors'),
      'help' => t("Bring in information about the domain this user is assigned to."),
      'base' => 'domain',
      'field' => 'domain_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Domain editors'),
    ),
  );
  return $data;
}

/**
 * Implement hook_views_handlers() to register all of the
 * basic handlers views uses.
 */
function domain_views_views_handlers() {
  $handlers = array(
     'info' => array(
      'path' => drupal_get_path('module', 'domain_views') .'/includes',
    ),
    'handlers' => array(
      // filter handlers
      'domain_views_handler_filter_domain_access_current_all' => array(
          'parent' => 'views_handler_filter_boolean_operator',
      ),
      'domain_views_handler_filter_domain_access_gid' => array(
        'parent' => 'views_handler_filter_in_operator',
      ),
      'domain_views_handler_filter_domain_domain_id' => array(
        'parent' => 'views_handler_filter_in_operator',
      ),
      // argument handlers
      'domain_views_handler_argument_domain_access_gid' => array(
        'parent' => 'views_handler_argument',
      ),
      // field handlers
      'domain_views_handler_field_domain_id' => array(
        'parent' => 'views_handler_field',
      ),
      'domain_views_handler_field_domain_sitename' => array(
        'parent' => 'views_handler_field',
      ),
    ),
  );
  return $handlers;
}

/**
 * Implement hook_views_plugins().
 */
function domain_views_views_plugins() {
  $path = drupal_get_path('module', 'domain_views') .'/includes';
  return array(
    'access' => array(
      'domain' => array(
        'title' => t('Domain'),
        'help' => t('Will be available only on selected domains.'),
        'handler' => 'domain_views_plugin_access',
        'uses options' => TRUE,
        'path' => $path,
      ),
    ),
    'argument default' => array(
      'current_domain' => array(
        'title' => t('Current domain'),
        'handler' => 'domain_views_plugin_argument_default_current',
        'path' => $path,
        'parent' => 'fixed', // so that the parent class is included
      ),
    ),
    'cache' => array(
      'time_per_domain' =>array(
        'title' => t('Time-based Per Domain'),
        'help' => t('Time-based caching of views on a per domain basis. This is necessary if you want to cache a view that filters on "current domain".'),
        'handler' => 'domain_views_plugin_cache_time',
        'uses options' => TRUE,
        'path' => $path,
        'parent' => 'time',
      ),
    )
  );
}

/**
 * Implement hook_views_query_substitutions ()
 */
function domain_views_views_query_substitutions($view) {
  return array('***CURRENT_DOMAIN***' => $GLOBALS['_domain']['domain_id']);
}

