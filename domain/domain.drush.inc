<?php

/**
 * @file
 * Drush commands for Domain Access.
 */

use Drupal\Component\Utility\Html;
use Drupal\domain\DomainInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Implements hook_drush_command().
 */
function domain_drush_command() {
  $items = [];

  $items['domain-list'] = [
    'description' => 'List active domains for the site.',
    'examples' => [
      'drush domain-list',
      'drush domains',
    ],
    'aliases' => ['domains'],
  ];
  $items['domain-add'] = [
    'description' => 'Add a new domain to the site.',
    'examples' => [
      'drush domain-add example.com \'My Test Site\'',
      'drush domain-add example.com \'My Test Site\' --inactive=1 --https==1',
      'drush domain-add example.com \'My Test Site\' --weight=10',
      'drush domain-add example.com \'My Test Site\' --validate=1',
    ],
    'arguments' => [
      'hostname' => 'The domain hostname to register (e.g. example.com).',
      'name' => 'The name of the site (e.g. Domain Two).',
    ],
    'options' => [
      'inactive' => 'Set the domain to inactive status if set.',
      'https' => 'Use https protocol for this domain if set.',
      'weight' => 'Set the order (weight) of the domain.',
      'is_default' => 'Set this domain as the default domain.',
      'validate' => 'Force a check of the URL response before allowing registration.',
    ],
  ];
  $items['domain-delete'] = [
    'description' => 'Delete a domain from the site.',
    'examples' => [
      'drush domain-delete example.com',
      'drush domain-delete 1',
    ],
    'arguments' => [
      'domain' => 'The numeric id or hostname of the domain to delete.',
    ],
  ];
  $items['domain-test'] = [
    'description' => 'Tests domains for proper response. If run from a subfolder, you must specify the --uri.',
    'examples' => [
      'drush domain-test',
      'drush domain-test example.com',
      'drush domain-test 1',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to test. If no value is passed, all domains are tested.',
    ],
    'options' => [
      'base_path' => 'The subdirectory name if Drupal is installed in a folder other than server root.',
    ],
  ];
  $items['domain-default'] = [
    'description' => 'Sets the default domain. If run from a subfolder, you must specify the --uri.',
    'examples' => [
      'drush domain-default example.com',
      'drush domain-default 1',
      'drush domain-default 1 --validate=1',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to make default.',
    ],
    'options' => [
      'validate' => 'Force a check of the URL response before allowing registration.',
    ],
  ];
  $items['domain-disable'] = [
    'description' => 'Sets a domain status to off.',
    'examples' => [
      'drush domain-disable example.com',
      'drush domain-disable 1',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to disable.',
    ],
  ];
  $items['domain-enable'] = [
    'description' => 'Sets a domain status to on.',
    'examples' => [
      'drush domain-disable example.com',
      'drush domain-disable 1',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to enable.',
    ],
  ];
  $items['domain-name'] = [
    'description' => 'Changes a domain label.',
    'examples' => [
      'drush domain-name example.com Foo',
      'drush domain-name 1 Foo',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to relabel.',
      'name' => 'The name to use for the domain.',
    ],
  ];
  $items['domain-machine-name'] = [
    'description' => 'Changes a domain name.',
    'examples' => [
      'drush domain-machine-name example.com foo',
      'drush domain-machine-name 1 foo',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to rename.',
      'name' => 'The machine-readable name to use for the domain.',
    ],
  ];
  $items['domain-scheme'] = [
    'description' => 'Changes a domain scheme.',
    'examples' => [
      'drush domain-scheme example.com https',
      'drush domain-scheme 1 https',
    ],
    'arguments' => [
      'domain_id' => 'The numeric id or hostname of the domain to change.',
      'scheme' => 'The URL schema (http or https) to use for the domain.',
    ],
  ];
  $items['generate-domains'] = [
    'description' => 'Generate domains for testing.',
    'arguments' => [
      'primary' => 'The primary domain to use. This will be created and used for *.example.com hostnames.',
    ],
    'options' => [
      'count' => 'The count of extra domains to generate. Default is 15.',
      'empty' => 'Pass empty=1 to truncate the {domain} table before creating records.',
    ],
    'examples' => [
      'drush domain-generate example.com',
      'drush domain-generate example.com --count=25',
      'drush domain-generate example.com --count=25 --empty=1',
      'drush gend',
      'drush gend --count=25',
      'drush gend --count=25 --empty=1',
    ],
    'aliases' => ['gend'],
  ];
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
 * Shows the domain list.
 */
function drush_domain_list() {
  $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultipleSorted(NULL, TRUE);

  if (empty($domains)) {
    drush_print(dt('No domains have been created. Use drush domain-add to create one.'));
    return;
  }

  $header = [
    'weight' => dt('Weight'),
    'name' => dt('Name'),
    'hostname' => dt('Hostname'),
    'scheme' => dt('Scheme'),
    'status' => dt('Status'),
    'is_default' => dt('Default'),
    'domain_id' => dt('Domain Id'),
    'id' => dt('Machine name'),
  ];
  $rows = [array_values($header)];
  foreach ($domains as $domain) {
    $row = [];
    foreach ($header as $key => $name) {
      $row[] = Html::escape($domain->get($key));
    }
    $rows[] = $row;
  }

  drush_print_table($rows, TRUE);
}

/**
 * Generates a list of domains for testing.
 *
 * In my environment, I name hostnames one.* two.* up to ten. I also use
 * foo.* bar.* and baz.*. We also want a non-hostname here and use
 * myexample.com.
 *
 * The script may also add test1, test2, test3 up to any number to test a
 * large number of domains. This test is mostly for UI testing.
 *
 * @param string $primary
 *   The root domain to use for domain creation.
 */
function drush_domain_generate_domains($primary = 'example.com') {
  // Check the number of domains to create.
  $count = drush_get_option('count');
  $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(NULL, TRUE);
  if (empty($count)) {
    $count = 15;
  }
  // Ensure we don't duplicate any domains.
  $existing = [];
  if (!empty($domains)) {
    foreach ($domains as $domain) {
      $existing[] = $domain->getHostname();
    }
  }
  // Set up one.* and so on.
  $names = [
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
  ];
  // Set the creation array.
  $new = [$primary];
  foreach ($names as $name) {
    $new[] = $name . '.' . $primary;
  }
  // Include a non hostname.
  $new[] = 'my' . $primary;
  // Filter against existing so we can count correctly.
  $prepared = [];
  foreach ($new as $key => $value) {
    if (!in_array($value, $existing)) {
      $prepared[] = $value;
    }
  }
  // Add any test domains that have numeric prefixes. We don't expect these URLs
  // to work, and mainly use these for testing the user interface.
  $needed = $count - count($prepared);
  for ($i = 1; $i <= $needed; $i++) {
    $prepared[] = 'test' . $i . '.' . $primary;
  }

  // Get the initial item weight for sorting.
  $start_weight = count($domains);
  $prepared = array_slice($prepared, 0, $count);

  // Create the domains.
  foreach ($prepared as $key => $item) {
    $hostname = mb_strtolower($item);
    $values = [
      'name' => ($item != $primary) ? ucwords(str_replace(".$primary", '', $item)) : \Drupal::config('system.site')->get('name'),
      'hostname' => $hostname,
      'scheme' => 'http',
      'status' => 1,
      'weight' => ($item != $primary) ? $key + $start_weight + 1 : -1,
      'is_default' => 0,
      'id' => \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($hostname),
    ];
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
    domain_drush_create($domain);
  }

  // If nothing created, say so.
  if (empty($new)) {
    drush_print(dt('No new domains were created.'));
  }
}

/**
 * Validates the domain generation script.
 *
 * @param string $primary
 *   The root domain to use for domain creation.
 */
function drush_domain_generate_domains_validate($primary = 'example.com') {
  if ($empty = drush_get_option('empty')) {
    db_query("TRUNCATE TABLE {domain}");
  }
  return;
  // TODO: Add validation.
}

/**
 * Adds a new domain.
 *
 * @param string $hostname
 *   The domain name to register.
 * @param string $name
 *   The name to use for this domain.
 */
function drush_domain_add($hostname, $name) {
  $records_count = \Drupal::entityTypeManager()->getStorage('domain')->getQuery()->count()->execute();
  $start_weight = $records_count + 1;
  $hostname = mb_strtolower($hostname);
  /** @var \Drupal\domain\DomainStorageInterface $domain_storage */
  $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
  $values = [
    'hostname' => $hostname,
    'name' => $name,
    'status' => (!drush_get_option('invalid')) ? 1 : 0,
    'scheme' => (!drush_get_option('https')) ? 'http' : 'https',
    'weight' => ($weight = drush_get_option('weight')) ? $weight : $start_weight + 1,
    'is_default' => ($is_default = drush_get_option('is_default')) ? $is_default : 0,
    'id' => $domain_storage->createMachineName($hostname),
    'validate_url' => (drush_get_option('validate')) ? 1 : 0,
  ];
  $domain = $domain_storage->create($values);
  domain_drush_create($domain);
}

/**
 * Validates the domain add command arguments.
 *
 * @param string $hostname
 *   The domain name to register.
 * @param string $name
 *   The name to use for this domain.
 *
 * @return bool
 *   TRUE when validation passed, FALSE otherwise.
 */
function drush_domain_add_validate($hostname, $name) {
  $errors = domain_drush_validate_domain($hostname);
  if (!empty($errors)) {
    return drush_set_error('domain', $errors);
  }
  elseif (\Drupal::entityTypeManager()->getStorage('domain')->loadByHostname($hostname)) {
    return drush_set_error('domain', dt('The hostname is already registered.'));
  }
  return TRUE;
}

/**
 * Creates a domain record.
 *
 * @param Drupal\domain\DomainInterface $domain
 *   A domain entity.
 */
function domain_drush_create(DomainInterface $domain) {
  if ($error = domain_drush_check_response($domain)) {
    drush_set_error('hostname', $error);
  }
  else {
    $domain->save();
    if ($domain->getDomainId()) {
      drush_print(dt('Created @name at @domain.', ['@name' => $domain->label(), '@domain' => $domain->getHostname()]));
    }
    else {
      drush_print(dt('The request could not be completed.'));
    }
  }
}

/**
 * Runs a check to ensure that the domain is responsive.
 *
 * @param Drupal\domain\DomainInterface $domain
 *   A domain entity.
 *
 * @return string
 *   An error message if the domain url does not validate. Else empty.
 */
function domain_drush_check_response(DomainInterface $domain) {
  // Check the domain response. First, clear the path value.
  if ($domain->validate_url) {
    $domain->setPath();
    try {
      $response = $domain->getResponse();
    }
    // We cannot know which Guzzle Exception class will be returned; be generic.
    catch (RequestException $e) {
      watchdog_exception('domain', $e);
      // File a general server failure.
      $domain->setResponse(500);
    }
    // If validate_url is set, then we must receive a 200 response.
    if ($domain->getResponse() != 200) {
      if (empty($response)) {
        $response = 500;
      }
      return dt('The server request to @url returned a @response response. To proceed, disable the test of the server response by leaving off the --validate flag.', ['@url' => $domain->getPath(), '@response' => $response]);
    }
  }
}

/**
 * Validates a domain.
 *
 * @param string $hostname
 *   The domain name to validate for syntax and uniqueness.
 *
 * @return array
 *   An array of errors encountered.
 *
 * @see domain_validate()
 */
function domain_drush_validate_domain($hostname) {
  /** @var \Drupal\domain\DomainValidatorInterface $validator */
  $validator = \Drupal::service('domain.validator');
  return $validator->validate($hostname);
}

/**
 * Deletes a domain record.
 *
 * @param string $argument
 *   The domain_id to delete. Pass 'all' to delete all records.
 */
function drush_domain_delete($argument = NULL) {
  if (is_null($argument)) {
    drush_set_error('domain', dt('You must specify a domain to delete.'));
  }
  if ($argument == 'all') {
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(NULL, TRUE);
    if (empty($domains)) {
      drush_print(dt('There are no domains to delete.'));
      return;
    }
    $content = drush_choice([1 => dt('Delete all domains')], dt('This action may not be undone. Continue?'), '!value');
    if (empty($content)) {
      return;
    }
  }
  // Resolve the domain.
  elseif ($domain = drush_domain_get_from_argument($argument)) {
    if ($domain->isDefault()) {
      return drush_set_error('domain', dt('The primary domain may not be deleted. Use drush domain-default to set a new default domain.'));
    }
    $domains = [$domain];
  }
  else {
    return;
  }
  foreach ($domains as $domain) {
    $domain->delete();
    drush_print(dt('Domain record @domain deleted.', ['@domain' => $domain->getHostname()]));
  }

  return;

  // TODO: Set options for re-assigning content.
  $list = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(NULL, TRUE);
  $options = ['0' => dt('Do not reassign')];
  foreach ($list as $data) {
    if ($data->id() != $domain->id()) {
      $options[$data->getDomainId()] = $data->getHostname();
    }
  }
  $content = drush_choice($options, dt('Reassign content to:'), '!value');
  if (empty($content)) {
    return;
  }
  $users = drush_choice($options, dt('Reassign users to:'), '!value');
  if (empty($users)) {
    return;
  }
  $values['domain_access'] = (!empty($content)) ? $content : 'none';
  $values['domain_editor'] = (!empty($content)) ? $users : 'none';

  domain_delete($domain, $values);

  drush_print(dt('Domain record deleted.'));
}

/**
 * Tests a domain record for the proper HTTP response.
 *
 * @param string $argument
 *   The domain_id to test. Passing no value tests all records.
 */
function drush_domain_test($argument = NULL) {
  // TODO: This won't work in a subdirectory without a parameter.
  if ($base_path = drush_get_option('base_path')) {
    $GLOBALS['base_path'] = '/' . $base_path . '/';
  }
  if (is_null($argument)) {
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple(NULL, TRUE);
  }
  else {
    if ($domain = drush_domain_get_from_argument($argument)) {
      $domains = [$domain];
    }
    else {
      return;
    }
  }
  foreach ($domains as $domain) {
    if ($domain->getResponse() != 200) {
      drush_print(dt('Fail: !error. Please pass a --uri parameter or a --base_path to retest.', ['!error' => $domain->getResponse()]));
    }
    else {
      drush_print(dt('Success: !url tested successfully.', ['!url' => $domain->getPath()]));
    }
  }
}

/**
 * Sets the default domain id.
 */
function drush_domain_default($argument) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $validate = (drush_get_option('validate')) ? 1 : 0;
    $domain->addProperty('validate_url', $validate);
    if ($error = domain_drush_check_response($domain)) {
      drush_set_error('domain', $error);
    }
    else {
      $domain->saveDefault();
      drush_print(dt('!domain set to primary domain.', ['!domain' => $domain->getHostname()]));
    }
  }
}

/**
 * Disables a domain.
 */
function drush_domain_disable($argument) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    if ($domain->status()) {
      $domain->disable();
      drush_print(dt('!domain has been disabled.', ['!domain' => $domain->getHostname()]));
    }
    else {
      drush_print(dt('!domain is already disabled.', ['!domain' => $domain->getHostname()]));
    }
  }
}

/**
 * Enables a domain.
 */
function drush_domain_enable($argument) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    if (!$domain->status()) {
      $domain->enable();
      drush_print(dt('!domain has been enabled.', ['!domain' => $domain->getHostname()]));
    }
    else {
      drush_print(dt('!domain is already enabled.', ['!domain' => $domain->getHostname()]));
    }
  }
}

/**
 * Changes a domain name.
 */
function drush_domain_name($argument, $name) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $domain->saveProperty('name', $name);
  }
}

/**
 * Changes a domain machine_name.
 */
function drush_domain_machine_name($argument, $machine_name) {
  $machine_name = \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($machine_name);
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $results = \Drupal::entityTypeManager()
      ->getStorage('domain')
      ->loadByProperties(['machine_name' => $machine_name]);
    foreach ($results as $result) {
      if ($result->id() == $machine_name) {
        drush_print(dt('The machine_name @machine_name is being used by domain @hostname.', ['@machine_name' => $machine_name, '@hostname' => $result->getHostname()]));
        return;
      }
    }
    $domain->saveProperty('id', $machine_name);
  }
}

/**
 * Changes a domain scheme.
 */
function drush_domain_scheme($argument) {
  // Resolve the domain.
  if ($domain = drush_domain_get_from_argument($argument)) {
    $content = drush_choice([1 => dt('http'), 2 => dt('https')], dt('Select the default http scheme:'), '!value');
    if (empty($content)) {
      return;
    }
    $scheme = 'http';
    if ($content == 2) {
      $scheme = 'https';
    }
    $domain->saveProperty('scheme', $scheme);
  }
}

/**
 * Converts a domain string or domain_id to a $domain array.
 *
 * On failure, throws a drush error.
 */
function drush_domain_get_from_argument($argument) {
  $domain = \Drupal::entityTypeManager()->getStorage('domain')->load($argument);
  if (!$domain) {
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->loadByHostname($argument);
  }
  if (!$domain) {
    drush_set_error('domain', dt('Domain record not found.'));
    return NULL;
  }
  return $domain;
}
