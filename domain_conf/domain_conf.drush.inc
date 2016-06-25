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
  $items['domain-conf-get'] = array(
    'description' => 'Get a variable for a specific domain.',
    'examples' => array(
      'drush domain-conf-get 2 site_frontpage',
      'drush dc-get 2 site_frontpage',
      'drush dc-get all site_frontpage',
    ),
    'arguments' => array(
      'domain_id' => 'The domain_id for the setting. If set to "all" it will list the value for all domains.',
      'name' => 'The name variable to set (e.g. site_frontpage).',
    ),
    'aliases' => array('dc-get'),
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
      domain_conf_delete_variable($domain['domain_id'], $name);
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

function drush_domain_conf_get($domain_id, $name) {
  if ($domain_id != 'all') {
    $value = domain_conf_variable_get($domain_id, $name);
    drush_print($name . ": " . $value);
  }
  else {
    foreach (domain_domains(TRUE) as $domain) {
      $value = domain_conf_variable_get($domain['domain_id'], $name);
      $value = isset($value) ? $value : dt('No matching variable found.');
      drush_print(dt('Domain id: !domain_id, site: !sitename, !name: !value', array(
        '!domain_id' => $domain['domain_id'],
        '!sitename' => $domain['sitename'],
        '!value' => $value,
        '!name' => $name,
      )));
    }
  }
}

function drush_domain_conf_get_validate($domain_id = NULL, $name = NULL) {
  if (is_null($domain_id) | is_null($name)) {
    return drush_set_error('domain_conf', dt('Please enter both required arguments, "dc-get domain_id name".'));
  }
  $domain = domain_load($domain_id);
  if (!isset($domain['domain_id']) && $domain_id != 'all') {
    return drush_set_error('domain_conf', dt('The specified domain does not exist.'));
  }
}
