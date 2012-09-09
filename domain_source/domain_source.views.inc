<?php


/**
 * @file
 * Views integration for Domain Source.
 */

/**
 * Implements hook_views_data().
 */
function domain_source_views_data() {
  // Define the {domain_access} table.
  $data['domain_source']['table']['group'] = t('Domain Source');
  // Define the joins.
  $data['domain_source']['table']['join']['node'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
  );
  // Define the fields.
  $data['domain_source']['nid'] = array(
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
      'help' => t("Bring in information about the source domain this node is assigned to."),
      'base' => 'node',
      'field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Domain node'),
    ),
  );
  $data['domain_source']['domain_id'] = array(
    'title' => t('Domain Source ID'),
    'help' => t('The unique id key for the source domain.'),
    'field' => array(
      'handler' => 'domain_views_handler_field_domain_id',
      'click sortable' => TRUE,
    ),
    // Information for accepting a domain_id as a filter
    'filter' => array(
      'handler' => 'domain_source_handler_filter_domain_id',
    ),
    // Information for accepting a domain_id as an argument
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),    
    // Information for sorting on a domain_id.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_query_substitutions().
 *
 * @see class domain_source_handler_filter_domain_id.
 * @see http://drupal.org/node/1685658
 */
function domain_source_views_query_substitutions($view) {
  return array(
    '***CURRENT_DOMAIN_SOURCE***' => DOMAIN_SOURCE_USE_ACTIVE,
  );
}
