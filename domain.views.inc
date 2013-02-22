<?php

/**
 * @file
 * Provide views data and handlers for domain.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_data().
 */
function domain_views_data() {
  // Define tables for making possible use domain entity in Views.
  $data['domain']['table']['group'] = t('Domains');
  $data['domain']['table']['entity type'] = 'domain';
  // @TODO: port the wizard.
  # $data['node']['table']['wizard_id'] = 'domain';

  // Advertise this table as a possible base table
  $data['domain']['table']['base'] = array(
    'field' => 'domain_id',
    'title' => t('Domain'),
    'weight' => -10,
    'access query tag' => 'domain_access', // @TODO
    'defaults' => array(
      'field' => 'name',
    ),
  );

  // domain_id
  $data['domain']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('The domain ID.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // hostname
  $data['domain']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('URL hostname for the domain record.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // name
  $data['domain']['name'] = array(
    'title' => t('Name'),
    'help' => t('The human-readable name of the domain record.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // URL scheme.
  $data['domain']['scheme'] = array(
    'title' => t('Scheme'),
    'help' => t('URI scheme element for the domain record.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Entity status.
  $data['domain']['status'] = array(
    'title' => t('Status'),
    'help' => t('Domain record status.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
      // Note that you can override the field-wide label:
      'label' => t('Active'),
      // This setting is used by the boolean filter handler, as possible option.
      'type' => 'yes-no',
      // use boolean_field = 1 instead of boolean_field <> 0 in WHERE statment.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Sort order.
  $data['domain']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('Domain record sort order.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Default domain marker.
  $data['domain']['is_default'] = array(
    'title' => t('Default domain'),
    'help' => t('Indicates that the domain record is the default.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
      // Note that you can override the field-wide label:
      'label' => t('Default'),
      // This setting is used by the boolean filter handler, as possible option.
      'type' => 'yes-no',
      // use boolean_field = 1 instead of boolean_field <> 0 in WHERE statment.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Machine name.
  $data['domain']['machine_name'] = array(
    'title' => t('Machine name'),
    'help' => t('The machine-readable name of the domain record.'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  return $data;
}
