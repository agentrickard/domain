<?php


/**
 * @file
 * Drush commands for Domain Access.
 */

/**
 * Implements hook_drush_command().
 */
function domain_drush_command() {
  $items = array();

  $items['domain-list'] = array(
    'description' => 'List active domains for the site.',
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function domain_drush_help($section) {
  $items = domain_drush_command();
  $name = str_replace('domain:', '', $section);
  if (isset($items[$name])) {
    return dt($items[$name]['description']);
  }
}

/**
 * Show the domain list.
 */
function drush_domain_list() {
  $domains = domain_domains();
  $header = array(
    'sitename' => dt('sitename'),
    'subdomain' => dt('domain'),
    'scheme' => dt('scheme'),
    'valid' => dt('active'),
    'domain_id' => dt('domain_id'),
  );
  $rows = array(array_values($header));
  foreach ($domains as $domain) {
    $row = array();
    foreach ($header as $key => $name) {
      $row[] = check_plain($domain[$key]);
    }
    $rows[] = $row;
  }
  drush_print_table($rows, TRUE);
}
