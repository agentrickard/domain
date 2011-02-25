<?php


/**
 * @file
 * Drush commands for Domain Conf.
 */

/**
 * Implements hook_drush_command().
 */
function domain_conf_drush_command() {
  $items = array();

  $items['domain-conf-set'] = array(
    'description' => 'Set a variable for a specific domain.',
    'examples' => array(
      'drush domain-conf-set 2 site_frontpage /home ',
      'drush dc-set 2 site_frontpage /home',
      'drush dc-set all site_frontpage /home',
    ),
    'arguments' => array(
      'domain_id' => 'The domain_id for the setting. If set to "all" it will be applied to all domains.',
      'name' => 'The name variable to set (e.g. site_frontpage).',
      'value' => 'The value to set for this variable.'
    ),
    'aliases' => array('dc-set'),
  );
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function domain_conf_drush_help($section) {
  $items = domain_conf_drush_command();
  $name = str_replace('domain:', '', $section);
  if (isset($items[$name])) {
    return dt($items[$name]['description']);
  }
}

function drush_domain_conf_set($domain_id, $name, $value) {
  if ($domain_id != 'all') {
    domain_conf_variable_set($domain_id, $name, $value);
    drush_print('Variable set.');
  }
  else {
    variable_set($name, $value);
    foreach (domain_domains(TRUE) as $domain) {
      domain_conf_variable_delete($domain['domain_id'], $name);
    }
    drush_print('Variable set for all domains.');
  }
}

function drush_domain_conf_set_validate($domain_id = NULL, $name = NULL, $value = NULL) {
  if (is_null($domain_id) | is_null($name) || is_null($value)) {
    return drush_set_error('domain_conf', dt('Please enter all three required arguments, "dc-set domain_id name value".'));
  }
  $domain = domain_load($domain_id);
  if (!isset($domain['domain_id']) && $domain_id != 'all') {
    return drush_set_error('domain_conf', dt('The specified domain does not exist.'));
  }
}
