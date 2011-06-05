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
    'description' => 'List active domains for the site.',
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
    'is_default' => dt('primary'),
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
  // In the future, we have to check for 'primary' status, too.
  $start_weight = (int) db_query("SELECT weight FROM {domain} ORDER BY weight DESC")->fetchField();
  $record = array(
    'subdomain' => strtolower($subdomain),
    'sitename' => $sitename,
    'valid' => (!drush_get_option('invalid')) ? 1 : 0,
    'scheme' => (!drush_get_option('https')) ? 'http' : 'https',
    'weight' => ($weight = drush_get_option('invalid')) ? $weight : $start_weight + 1,
    #'primary' => (drush_get_option('primary')) ? 1 : 0,
  );
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
