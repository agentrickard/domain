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
    'examples' => array(
      'drush domain-list',
      'drush domains',
    ),
    'aliases' => array('domains'),
  );
  $items['domain-add'] = array(
    'description' => 'Add a new domain to the site.',
    'examples' => array(
      'drush domain-add example.com \'My Test Site\'',
      'drush domain-add example.com \'My Test Site\' --inactive=1 --https==1',
      'drush domain-add example.com \'My Test Site\' --weight=10',
    ),
    'arguments' => array(
      'domain' => 'The domain to register (e.g. example.com).',
      'sitename' => 'The name of the site (e.g. Domain Two).',
    ),
    'options' => array(
      'inactive' => 'Set the domain to inactive status if set.',
      'https' => 'Use https protocol for this domain if set.',
      'weight' => 'Set the order (weight) of the domain.',
    ),
  );
  $items['domain-delete'] = array(
    'description' => 'Delete a domain from the site.',
    'examples' => array(
      'drush domain-delete example.com',
      'drush domain-delete 1',
    ),
    'arguments' => array(
      'domain' => 'The domain to register (e.g. example.com or the domain_id).',
    ),
  );
  $items['domain-test'] = array(
    'description' => 'Tests domains for proper response. If run from a subfolder, you must specify the --uri.',
    'examples' => array(
      'drush domain-test',
      'drush domain-test example.com',
      'drush domain-test 1',
    ),
    'arguments' => array(
      'domain' => 'The domain to register (e.g. example.com or the domain_id). If no value is passed, all domains are tested.',
    ),
  );
  $items['domain-default'] = array(
    'description' => 'Sets the default domain. If run from a subfolder, you must specify the --uri.',
    'examples' => array(
      'drush domain-default example.com',
      'drush domain-default 1',
      'drush domain-default 1 --skip_check=1',
    ),
    'arguments' => array(
      'domain' => 'The domain to make default (e.g. example.com or the domain_id).',
    ),
    'options' => array(
      'skip_check' => 'Bypass the domain response test.'
    ),
  );
  $items['generate-domains'] = array(
    'description' => 'Generate domains for testing.',
    'arguments' => array(
      'primary' => 'The primary domain to use. This will be created and used for *.example.com subdomains.',
    ),
    'options' => array(
      'count' => 'The count of extra domains to generate. Default is 15.',
      'empty' => 'Pass empty=1 to truncate the {domain} table before creating records.'
    ),
    'examples' => array(
      'drush domain-generate example.com',
      'drush domain-generate example.com --count=25',
      'drush domain-generate example.com --count=25 --empty=1',
      'drush gend',
      'drush gend --count=25',
      'drush gend --count=25 --empty=1',
    ),
    'aliases' => array('gend'),
  );
  $items['domain-repair'] = array(
    'description' => 'Updates domain_id 0 records in dependent tables.',
    'examples' => array(
      'drush domain-repair',
    ),
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
    'weight' => dt('weight'),
    'sitename' => dt('sitename'),
    'subdomain' => dt('domain'),
    'scheme' => dt('scheme'),
    'valid' => dt('active'),
    'is_default' => dt('is_default'),
    'domain_id' => dt('domain_id'),
    'machine_name' => dt('machine_name'),
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

/**
 * Generate a list of domains for testing.
 *
 * In my environment, I name subdomains one.* two.* up to ten. I also use
 * foo.* bar.* and baz.*. We also want a non-subdomain here and use
 * myexample.com.
 *
 * The script may also add test1, test2, test3 up to any number to test a
 * large number of domains. This test is mostly for UI testing.
 *
 * @param $primary
 *   The root domain to use for domain creation.
 *
 * @return
 *   A list of the domains created.
 */
function drush_domain_generate_domains($primary = 'example.com') {
  // Check the number of domains to create.
  $count = drush_get_option('count');
  $domains = domain_domains(TRUE);
  if (empty($count)) {
    $count = 15;
  }
  // Ensure we don't duplicate any domains.
  $existing = array();
  if (!empty($domains)) {
    foreach ($domains as $domain) {
      $existing[] = $domain['subdomain'];
    }
  }
  // Set up one.* and so on.
  $names = array(
    'one',
    'two',
    'three',
    'four',
    'five',
    'six',
    'seven',
    'eight',
    'nine',
    'ten',
    'foo',
    'bar',
    'baz',
  );
  // Set the creation array.
  $new = array($primary);
  foreach ($names as $name) {
    $new[] = $name . '.' . $primary;
  }
  // Include a non subdomain.
  $new[] = 'my' . $primary;
  // Filter against existing so we can count correctly.
  $prepared = array();
  foreach ($new as $key => $value) {
    if (!in_array($value, $existing)) {
      $prepared[] = $value;
    }
  }
  // Add any test domains.
  if ($count > 15 || empty($prepared)) {
    // Find the highest numbered test domain.
    $start = db_query("SELECT sitename FROM {domain} WHERE sitename LIKE 'test%' ORDER BY domain_id DESC")->fetchField();
    $start = (int) str_ireplace('test', '', $start);
    $j = count($prepared);
    for ($i = $start + 1; $j <= $count; $i++) {
      $prepared[] = 'test' . $i . '.' . $primary;
      $j++;
    }
  }
  // Get the initial item weight for sorting.
  $start_weight = db_query("SELECT weight FROM {domain} ORDER BY weight DESC")->fetchField();
  $prepared = array_slice($prepared, 0, $count);
  foreach ($prepared as $key => $item) {
    $record = array(
      'sitename' => ($item != $primary) ? ucwords(str_replace(".$primary", '', $item)) : variable_get('site_name', 'Drupal'),
      'subdomain' => strtolower($item),
      'scheme' => 'http',
      'valid' => 1,
      'weight' => ($item != $primary) ? $key + $start_weight + 1 : -1,
      'is_default' => 0,
    );
    $created = domain_save($record, $record);
    drush_print(dt('Created !domain.', array('!domain' => $record['sitename'])));
  }
  db_update('domain')
    ->condition('subdomain', $primary)
    ->fields(array('is_default' => 1))
    ->execute();
  if (empty($new)) {
    drush_print(dt('No new domains were created.'));
  }
}

/**
 * Validate the domain generation script.
 *
 * @param $primary
 *   The root domain to use for domain creation.
 */
function drush_domain_generate_domains_validate($primary = 'example.com') {
  if ($empty = drush_get_option('empty')) {
    db_query("TRUNCATE TABLE {domain}");
  }
  $error = domain_valid_domain($primary);
  if (!empty($error)) {
    return drush_set_error('domain', $error);
  }
}

/**
 * Add a new domain.
 *
 * @param $subdomain
 *   The domain name to register.
 * @param $sitename
 *   The sitename to use for this domain.
 *
 * @return
 *   The domain created or an error message.
 */
function drush_domain_add($subdomain, $sitename) {
  $start_weight = (int) db_query("SELECT weight FROM {domain} ORDER BY weight DESC")->fetchField();
  $record = array(
    'subdomain' => strtolower($subdomain),
    'sitename' => $sitename,
    'valid' => (!drush_get_option('invalid')) ? 1 : 0,
    'scheme' => (!drush_get_option('https')) ? 'http' : 'https',
    'weight' => ($weight = drush_get_option('weight')) ? $weight : $start_weight + 1,
    'is_default' => ($is_default = drush_get_option('is_default')) ? $is_default : 0,
  );
  if (!empty($record['is_default'])) {
    $error = domain_check_response($record, TRUE);
    if ($error) {
      return drush_set_error('domain', $error);
    }
  }
  $domain = domain_save($record, $record);
  if (isset($domain['domain_id'])) {
    drush_print(dt('Created @domain for @sitename.', array('@domain' => $domain['subdomain'], '@sitename' => $domain['sitename'])));
  }
  else {
    drush_print(dt('The request could not be completed.'));
  }
}

/**
 * Validate the domain add script.
 *
 * @param $subdomain
 *   The domain name to register.
 * @param $sitename
 *   The sitename to use for this domain.
 */
function drush_domain_add_validate($subdomain, $sitename) {
  $error = domain_drush_validate_domain($subdomain);
  if (!empty($error)) {
    return drush_set_error('domain', $error);
  }
}

/**
 * Drush version of domain_validate.
 *
 * @param $subdomain
 *   The domain name to validate for syntax and uniqueness.
 *
 * @return
 *  An array of errors encountered.
 *
 * @see domain_validate()
 */
function domain_drush_validate_domain($subdomain) {
  $error = domain_validate($subdomain);
  $output = '';
  foreach ($error as $msg) {
    $output .= $msg;
  }
  return $output;
}

/**
 * Delete a domain record.
 */
function drush_domain_delete($argument = NULL) {
  if (is_null($argument)) {
    drush_set_error('domain', dt('You must specify a domain to delete.'));
  }
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $domains = array($domain);
  }
  else {
    return;
  }
  if (!empty($domain['is_default'])) {
    return drush_set_error('domain', dt('The primary domain may not be deleted.'));
  }
  // Set options for re-assigning content.
  $list = domain_domains();
  $options = array('0' => t('Do not reassign'));
  foreach ($list as $data) {
    if ($data['domain_id'] != $domain['domain_id']) {
      $options[$data['domain_id']] = $data['subdomain'];
    }
  }
  $content = drush_choice($options, t('Reassign content to:'), '!value');
  if (empty($content)) {
    return;
  }
  $users = drush_choice($options, t('Reassign users to:'), '!value');
  if (empty($users)) {
    return;
  }
  $values['domain_access'] = (!empty($content)) ? $content : 'none';
  $values['domain_editor'] = (!empty($content)) ? $users : 'none';

  domain_delete($domain, $values);

  drush_print(dt('Domain record deleted.'));
}


/**
 * Test a domain record.
 */
function drush_domain_test($argument = NULL) {
  if (is_null($argument)) {
    $domains = domain_domains();
  }
  else {
    if ($domain = drush_domain_get_from_argument($argument)) {
      $domains = array($domain);
    }
    else {
      return;
    }
  }
  foreach ($domains as $domain) {
    $error = domain_check_response($domain, TRUE);
    if ($error) {
      drush_print(dt('Fail: !error' , array('!error' => $error)));
    }
    else {
      drush_print(dt('Success: !url tested successfully.', array('!url' => domain_get_path($domain))));
    }
  }
}

/**
 * Set the default domain id.
 */
function drush_domain_default($argument) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $domains = array($domain);
  }
  else {
    return;
  }
  // Check for domain response.
  $check = drush_get_option('skip_check');
  if (empty($check)) {
    $error = domain_check_response($domain, TRUE);
    if ($error) {
      drush_set_error($error);
      drush_print(dt('You may disable this error by passing --skip_check=1.'));
      return;
    }
  }
  $domain['is_default'] = 1;
  domain_save($domain, $domain);
  drush_print(dt('!domain set to primary domain.', array('!domain' => $domain['subdomain'])));
}

/**
 * Converts a domain string or domain_id to a $domain array.
 *
 * On failure, throws a drush error.
 */
function drush_domain_get_from_argument($argument) {
  $domain = domain_lookup($argument);
  if ($domain == -1) {
    $domain = domain_lookup(NULL, $argument);
  }
  if ($domain == -1) {
    drush_set_error('domain', dt('Domain record not found.'));
    return FALSE;
  }
  return $domain;
}

/**
 * Replaces domain_id 0 records with the default domain.
 */
function drush_domain_repair() {
  $list = domain_update_module_check();
  if (empty($list)) {
    drush_print(dt('All tables are up-to-date.'));
    return;
  }
  $modules = array();
  drush_print(dt('The following modules require a data update:'));
  drush_print();
  foreach ($list as $item) {
    drush_print(' * ' . $item['name']);
  }
  drush_print();
  $choice = drush_choice(array(1 => dt('Update')), dt('Update database?'), '!value');
  if ($choice) {
    $success = domain_update_zero_records(domain_update_tables($list));
    if ($success) {
      drush_print(dt('!count table(s) updated successfully.', array('!count' => count($list))));
    }
  }
}
