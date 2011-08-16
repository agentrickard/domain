<?php

/**
 * @file
 * Administration functions for the domain module.
 *
 * These functions only need to be accessed from admin/structure/domain, so we put them in a separate include file.
 * @ingroup domain
 */

/**
 * The main administration page, a list of active domains.
 *
 * @return
 *  A sortable table of active domain records, or an error message.
 */
function domain_view() {
  $build = array();
  $_domain = domain_get_domain();
  $build['header']['#prefix'] = '<p>';
  $build['header']['#markup'] = t('The following domains have been created for your site.  
    The currently active domain <strong>is shown in boldface</strong>.  You
    may click on a domain to change the currently active domain.  Your default
    domain has an ID of %id.', array('%id' => variable_get('domain_default', 0))
  );
  $build['header']['#suffix'] = '</p>';
  $header = array(
    array('data' => t('Id'), 'field' => 'd.domain_id'),
    array('data' => t('Domain'), 'field' => 'd.subdomain'),
    array('data' => t('Site name'), 'field' => 'd.sitename'),
    array('data' => t('Status'), 'field' => 'd.valid'),
    array('data' => t('Scheme'), 'field' => 'd.scheme'),
  );
  // Get header elements from other modules
  $extra = module_invoke_all('domainview', 'header');
  $header = array_merge($header, $extra);
  $header[] = array('data' => t('Actions'));

  // Cannot use domain_domains() here because we need to sort the output.
  $domains = array();
  $actions = array();

  // Set up the base query.
  $query = db_select('domain', 'd')
    ->fields('d', array('domain_id', 'sitename', 'subdomain', 'scheme', 'valid'));
  // Get any tablesort sql from other modules.
  module_invoke_all('domainview', 'query', array(), $query);
  // Add the tablesort.
  $query = $query
    ->extend('TableSort')
    ->orderByHeader($header);
  $query = $query->extend('PagerDefault')->limit(variable_get('domain_list_size', DOMAIN_LIST_SIZE));
  // Get the domains.
  $result = $query->execute();
  foreach ($result as $data) {
    $domains[] = (array) $data;
  }
  foreach ($domains as $domain) {
    // Let submodules overwrite the defaults, if they wish.
    $domain = domain_api($domain);
    $link = l($domain['subdomain'], domain_get_uri($domain), array('absolute' => TRUE));
    $actions = array();
    if ($domain['domain_id'] > 0) {
      $actions = array();
      $actions[] = l(t('edit'), 'admin/structure/domain/edit/' . $domain['domain_id']);
      $actions[] = l(t('delete'), 'admin/structure/domain/delete/' . $domain['domain_id']);
    }
    // Add advanced settings from other modules.
    $items = array();
    $items = module_invoke_all('domainlinks', $domain);
    if (!empty($items)) {
      foreach ($items as $item) {
        if (!empty($item)) {
          $actions[] = l($item['title'], $item['path']);
        }
      }
    }
    // Set the valid flag.
    ($domain['valid'] == 1) ? $valid = t('Active') : $valid = t('Inactive');
    $row = array($domain['domain_id'], ($domain['domain_id'] == $_domain['domain_id']) ? '<strong>' . $link . '</strong>' : $link, filter_xss_admin($domain['sitename']), $valid, $domain['scheme']);
    // Let other modules add data.
    $data = array();
    foreach (module_implements('domainview') as $module) {
      $add = module_invoke($module, 'domainview', 'data', $domain);
      $row = array_merge($row, $add);
    }
    // Add the actions.
    $list = array('items' => $actions);
    $row[] = theme('item_list', $list);
    $rows[] = $row;
  }
  if (!empty($rows)) {
    $build['content'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' =>  array('id' => 'domain-list'),
    );
    $build['pager']['#theme'] = 'pager';
    return $build;
  }
  else {
    $build = array();
    $build['no_content']['#prefix'] = '<p>';
    $build['no_content']['#markup'] = t('No domains have been configured.');
    $build['no_content']['#suffix'] = '</p>';
    return $build;
  }
}

/**
 * Module settings and behaviors.
 */
function domain_configure() {
  if (empty($_POST)) {
    // Is the module installed correctly?
    module_invoke_all('domaininstall');
  }
  // Return the configuration form.
  return drupal_get_form('domain_configure_form');
}

/**
 * FormsAPI for configuring the domain module.
 */
function domain_configure_form($form, &$form_state, $user_submitted = FALSE) {
  $root = variable_get('domain_root', '');
  if (empty($root)) {
    domain_set_primary_domain();
  }
  $form = array();

  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default domain settings'),
    '#collapsible' => TRUE
  );
  $sitename = variable_get('site_name', 'Drupal');
  $form['domain']['domain_root'] = array(
    '#type' => 'textfield',
    '#title' => t('Primary domain name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => variable_get('domain_root', ''),
    '#description' => t('The primary domain for your site.  Typically <em>example.com</em> or <em>www.example.com</em>.  Do not use http or slashes.
      <br />This domain will be used as the default URL for your site.
      <br />If an invalid domain is requested, users will be sent next available domain or to the primary domain.')
  );
  $form['domain']['domain_sitename'] = array(
    '#type' => 'textfield',
    '#title' => t('Site name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => variable_get('domain_sitename', $sitename),
    '#description' => t('The site name to display for this domain.')
  );
  $form['domain']['domain_scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Domain URL scheme'),
    '#required' => TRUE,
    '#options' => array('http' => 'http://', 'https' => 'https://'),
    '#default_value' => variable_get('domain_scheme', 'http'),
    '#description' => t('The URL scheme for accessing the primary domain.')
  );
  $form['domain_behavior'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain module behaviors'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['domain_behavior']['domain_behavior'] = array(
    '#type' => 'radios',
    '#title' => t('New content settings'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_behavior', DOMAIN_INSTALL_RULE),
    '#options' => array(1 => t('Show on all affiliate sites'), 2 => t('Only show on selected sites')),
    '#description' => t('If set to <em>Show on all affiliate sites</em>, this value will automatically assign new content to all affiliates.<br />If set to <em>Only show on selected sites</em>, you must configure the <a href="!url">Node-type specific settings</a>.', array('!url' => url('admin/structure/domain/advanced')))
  );

  $form['domain_behavior']['domain_debug'] = array(
    '#type' => 'radios',
    '#title' => t('Debugging status'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_debug', 0),
    '#options' => array(0 => t('Do not show debugging output'), 1 => t('Show debugging output on node view')),
    '#description' => t('If set, users with the <em>set domain access</em> permission will be able to view the node access rules for each node. See the README for more details.')
  );

  $form['domain_behavior']['domain_force_admin'] = array(
    '#type' => 'radios',
    '#title' => t('Enforce rules on administrators'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_force_admin', 0),
    '#options' => array(0 => t('Do not enforce'), 1 => t('Restrict node views for administrators')),
    '#description' => t('If set, users with the <em>administer nodes</em> permission and user 1 <em>will view the site with Domain Access restrictions enforced</em>. See the README for more details.')
  );

  $options = array(
    'id' => t('Creation order, oldest > newest'),
    'rid' => t('Creation order, newest > oldest'),
    'name' => t('Sitename, A > Z'),
    'rname' => t('Sitename, Z > A'),
    'url' => t('URL, A > Z'),
    'rurl' => t('URL, Z > A')
  );
  $form['domain_behavior']['domain_sort'] = array(
    '#type' => 'radios',
    '#title' => t('Sort domain lists'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_sort', 'id'),
    '#options' => $options,
    '#description' => t('Controls the display of domain lists to end users.')
  );
  $form['domain_behavior']['domain_list_size'] = array(
    '#type' => 'select',
    '#title' => t('Domain list size'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_list_size', DOMAIN_LIST_SIZE),
    '#options' => drupal_map_assoc(array(5, 10, 20, 25, 30, 40, 50, 75, 100, 150, 200, 250, 500, 750, 1000)),
    '#description' => t('Sets a break point for the size of domain lists shown to users. After this point, user interfaces will use tables, pagination, and select lists to prevent too many domains from appearing in a list. <em>Note: setting this value higher than 200 may cause memory and display issues for your site.</em>')
  );
  $form['domain_behavior']['domain_select_format'] = array(
    '#type' => 'select',
    '#title' => t('Domain selection format'),
    '#required' => TRUE,
    '#default_value' => domain_select_format(),
    '#options' => array(t('Checkboxes'), t('Select list')),
    '#description' => t('Determines the display format of form elements that display domain lists.')
  );

  $form['domain_advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );

  $form['domain_advanced']['domain_www'] = array(
    '#type' => 'radios',
    '#title' => t('WWW prefix handling'),
    '#default_value' => variable_get('domain_www', 0),
    '#options' => array(0 => t('Process all host requests normally'), 1 => t('Treat www.*.example.com as an alias of *.example.com')),
    '#description' => t('If set, calls to www.* will be treated as if the www. did not exist.
    <em>Users will be taken from www.example.com to example.com, so your domains must be registered without the www. prefix.</em>')
  );

  $form['domain_advanced']['domain_search'] = array(
    '#type' => 'radios',
    '#title' => t('Search settings'),
    '#default_value' => variable_get('domain_search', 0),
    '#options' => array(0 => t('Search content for the current domain only'), 1 => t('Search all domains from any URL')),
    '#description' => t('Options for content searching.')
  );

  $form['domain_advanced']['domain_seo'] = array(
    '#type' => 'radios',
    '#title' => t('Search engine optimization'),
    '#default_value' => variable_get('domain_seo', 0),
    '#options' => array(0 => t('Do not rewrite URLs'), 1 => t('Rewrite all URLs to point to a single source')),
    '#description' => t('If rewrite is turned on, all node links will point to a single instance of the node.  This
      option reduces the chance that search engines will recognize duplicate content.')
  );

  $options = array('-1' => t('Do not change domain'));
  foreach (domain_domains() as $data) {
    // The domain must be valid.
    if ($data['valid']) {
      $options[$data['domain_id']] = $data['sitename'];
    }
  }

  $form['domain_advanced']['domain_default_source'] = array(
    '#type' => 'select',
    '#title' => t('Default source domain'),
    '#options' => $options,
    '#default_value' => variable_get('domain_default_source', 0),
    '#description' => t('When rewriting urls, nodes assigned to all affiliates will be sent to this domain. <em>NOTE: This option only fires if you enable SEO rewrites or use the Domain Source module.</em>'),
  );

  $form['domain_all'] = array(
    '#type' => 'fieldset',
    '#title' => t('Special page requests'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );

  $form['domain_all']['domain_grant_all'] = array(
    '#type' => 'textarea',
    '#rows' => 5,
    '#cols' => 40,
    '#default_value' => variable_get('domain_grant_all', "user/*/track"),
    '#description' => t('Content on these pages should be viewable on any domain.  Enter one path per line.
      You may use the * as a wildcard.  Use this for aggregate pages like those provided by <a href="!url">MySite</a> or if you
      intend to show all user posts on a specific page.  See the README for more details.', array('!url' => 'http://drupal.org/project/mysite'))
  );

  $form['domain_all']['domain_cron_rule'] = array(
    '#type' => 'checkbox',
    '#default_value' => variable_get('domain_cron_rule', 1),
    '#title' => t('Treat cron.php as a special page request.'),
    '#description' => t('Normally, you should leave this setting active.  See the README for more information.')
  );

  $form['domain_all']['domain_xmlrpc_rule'] = array(
    '#type' => 'checkbox',
    '#default_value' => variable_get('domain_xmlrpc_rule', 0),
    '#title' => t('Treat xmlrpc.php as a special page request.'),
    '#description' => t('Enable this setting if you have trouble with remote data calls over XMLRPC.  See the README for more information.')
  );

  $form['domain_paths'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node link patterns'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $form['domain_paths']['domain_paths'] = array(
    '#type' => 'textarea',
    '#rows' => 5,
    '#cols' => 40,
    '#default_value' => variable_get('domain_paths', "node/%n\r\nnode/%n/edit\r\ncomment/reply/%n\r\nnode/add/book/parent/%n\r\nbook/export/html/%n\r\nnode/%n/outline"),
    '#description' => t('When using SEO or other path rewrites, the following link paths should be turned into absolute URLs.  Enter
      the Drupal path of the link, using the <em>%n</em> placeholder to represent the node id.
      Enter one path per line.  See the README for more details.')
  );

  // Allow submodules to add elements to the form.
  $modules = module_implements('domainform');
  if (!empty($modules)) {
    foreach ($modules as $module) {
      $func = $module . '_domainform';
      $func($form);
    }
  }
  // Add our additional submit handlers.
  $form['#submit'] = array('domain_configure_form_submit');
  return system_settings_form($form);
}

/**
 * Validate the primary domain string.
 */
function domain_configure_form_validate($form, &$form_state) {
  if (!isset($form_state['values']['domain_root'])) {
    return;
  }
  $subdomain = $form_state['values']['domain_root'];
  $domain_changed = (bool) strcmp($form_state['values']['domain_root'], $form['domain']['domain_root']['#default_value']);
  if ($domain_changed) {
    $error_list = domain_validate($subdomain);
    foreach ($error_list as $error) {
      form_set_error('domain_root', $error);
    }
  }
}

/**
 * Save any changes to the primary domain record.
 */
function domain_configure_form_submit($form, &$form_state) {
  if (!isset($form_state['values']['domain_root'])) {
    return;
  }
  $subdomain = $form_state['values']['domain_root'];
  db_update('domain')
    ->condition('domain_id', 0)
    ->fields(array(
      'subdomain' => $subdomain,
      'sitename' => $form_state['values']['domain_sitename'],
      'scheme' => $form_state['values']['domain_scheme'],
      'valid' => 1,
    ))
    ->execute();
  // Allow other modules to respond to changes.
  module_invoke_all('domainupdate', 'update', domain_default(TRUE));
}

/**
 * Return an array of values suitable for both create and update sql statements
 *
 * The last element on this array is ignored by the create sql because it has
 * one fewer placeholder argument as it doesnt require a WHERE clause.
 *
 * @param $form_state
 *   From state from the submit_hook
 * @return array
 *   Array of domain values for insert and update sql operations
 */
function domain_values_from_form_state($form_state) {
  return array(
    'subdomain' => $form_state['values']['subdomain'],
    'sitename'  => $form_state['values']['sitename'],
    'scheme'    => $form_state['values']['scheme'],
    'valid'     => $form_state['values']['valid'],
    'domain_id' => $form_state['values']['domain_id'],
  );
}

/**
 * FormsAPI for editing a domain record
 *
 * @param $form
 *   The current form, passed by FormsAPI.
 * @param $form_state
 *   The current form state, passed by FormsAPI.
 * @param $domain
 *   An array containing the record from the {domain} table.
 * @param $arguments
 *   An array of additional hidden key/value pairs to pass to the form.
 *   Used by child modules to control behaviors.
 *   Currently supported arguments are:
 *    'user_submitted' => TRUE
 *    Indicates that a form should not process administrative messages and paths
 *    upon form submit.  Used by the Domain User module.
 */
function domain_form($form, &$form_state, $domain = array(), $arguments = array()) {
  $_domain = domain_get_domain();
  // This action should be performed from the primary domain unless overridden.
  if (!isset($arguments['ignore_domain_status_check'])) {
    domain_check_primary();
    if (empty($_POST) && !empty($domain)) {
      domain_check_response($domain);
    }
  }
  // Ensure indexes are set.
  foreach ($_domain as $key => $value) {
    if (!isset($domain[$key])) {
      $domain[$key] = NULL;
    }
  }
  $form = array();
  
  $form['#domain'] = $domain; 
  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain record'),
    '#collapsible' => TRUE
  );
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  if (!variable_get('domain_allow_non_ascii', FALSE)) {
    $description = t('Can only contain lowercase alphanumeric characters, dashes and a colon (if using alternative ports).');
  }
  else {
    $description = t('Can contain lowercase alphanumeric and ASCII characters, dashes and a colon (if using alternative ports).');
  }
  $form['domain']['subdomain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => $domain['subdomain'],
    '#description' => t('The allowed domain, using the full <em>path.example.com</em> format. Leave off the http:// and the trailing slash and do not include any paths.') . ' ' . $description
  );
  $form['domain']['sitename'] = array(
    '#type' => 'textfield',
    '#title' => t('Site name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => $domain['sitename'],
    '#description' => t('The human-readable name of this domain.')
  );
  $form['domain']['scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Domain URL scheme'),
    '#required' => TRUE,
    '#options' => array('http' => 'http://', 'https' => 'https://'),
    '#default_value' => !empty($domain['scheme']) ? $domain['scheme'] : 'http',
    '#description' => t('The URL scheme for accessing this domain.')
  );
  $form['domain']['valid'] = array(
    '#type' => 'radios',
    '#title' => t('Domain status'),
    '#required' => TRUE,
    '#options' => array(1 => t('Active'), 0 => t('Inactive')),
    '#default_value' => isset($domain['valid']) ? $domain['valid'] : 1,
    '#description' => t('Must be set to "Active" for users to navigate to this domain.')
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save domain record'));
  $form['#validate'][] = 'domain_form_validate';
  $form['#redirect'] = array('admin/structure/domain/view');
  return $form;
}

/**
 * Implement domain_form validate hook.
 */
function domain_form_validate($form, &$form_state) {
  $subdomain = $form_state['values']['subdomain'];
  $error_list = array();
  $domain_changed = (bool) strcmp($form_state['values']['subdomain'], $form['domain']['subdomain']['#default_value']);
  if ($domain_changed && !domain_unique_domain($subdomain)) {
    $error_list[] = t('The domain value must be unique.');
  }
  $error = domain_valid_domain($subdomain);
  if (!empty($error)) {
    $error_list[] = $error;
  }
  foreach ($error_list as $error) {
    form_set_error('subdomain', $error);
  }
}

/**
 * Implement domain_form submit hook.
 */
function domain_form_submit($form, &$form_state) {
  $values = domain_values_from_form_state($form_state);
  // Set the proper message
  if (!empty($values['domain_id'])) {
    $message = t('Domain record updated.');
  }
  else {
    $message = t('Domain record created.');
  }
  // Run the save routine.
  $domain = domain_save($values, $form_state);

  // If return is not a $domain array, something went wrong.
  if ($domain == -1) {
    $message = t('Domain record failed.');
  }

  // Hide the message for the Domain User module.
  if (empty($form_state['values']['domain_arguments']['user_submitted'])) {
    drupal_set_message($message);
  }

  return $domain;
}

/**
 * A delete confirmation form.
 *
 * @param $form_state
 *   The current form state, passed by FormsAPI.
 * @param $domain
 *   An array containing the record from the {domain} table.
 * @param $arguments
 *   An array of additional hidden key/value pairs to pass to the form.
 *   Used by child modules to control behaviors.
 *   Currently supported arguments are:
 *     'user_submitted' => TRUE
 *     Indicates that a form should not process administrative messages and paths
 *     upon form submit.  Used by the Domain User module.
 */
function domain_delete_form($form, &$form_state, $domain, $arguments = array()) {
  // This action should be performed from the primary domain.
  domain_check_primary();

  $form = array();
  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain'] = array('#type' => 'value', '#value' => $domain);

  return confirm_form(
    $form,
    t('Are you sure you wish to delete the domain record for <strong>%domain</strong>?', array('%domain' => $domain['subdomain'])),
    'admin/structure/domain/view',
    NULL,
    t('Delete domain record'),
    t('Cancel')
  );
}

/**
 * Implement domain_delete_form submit hook.
 */
function domain_delete_form_submit($form, &$form_state) {
  // Delete the domain.
  domain_delete($form_state['values']['domain'], $form_state['values']);

  // Hide the message from the Domain User module.
  if (empty($form_state['values']['domain_arguments']['user_submitted'])) {
    drupal_set_message(t('Domain record deleted.'));
    $form_state['redirect'] = 'admin/structure/domain/view';
  }
}

/**
 * Implement domain_advanced_form.
 *
 * @param $form
 *   The current form, passed by FormsAPI.
 * @param $form_state
 *   The current form state, passed by FormsAPI.
 */
function domain_advanced_form($form, &$form_state) {
  $form = array();
  $node_types = node_type_get_names();
  // In D7, type handling is strict, so make this value 0 or 1.
  // @TODO: clean up DOMAIN_INSTALL handling.
  $default = (int) variable_get('domain_behavior', DOMAIN_INSTALL_RULE);
  if ($default == 2) {
    $default = 0;
  }
  $form['domain_node'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain node types'),
    '#collapsible' => TRUE
  );
  $form['domain_node']['intro'] = array('#markup' => t('<p>Check the content types that should be published to all affiliates when new content is created.</p>'));
  foreach ($node_types as $key => $value) {
    $form['domain_node']['domain_node_' . $key] = array(
      '#type' => 'checkbox',
      '#title' => check_plain($value),
      '#default_value' => variable_get('domain_node_' . $key, $default),
    );
  }
  // Some editors will not have full node editing permissions.  This allows us
  // to give selected permissions for nodes within the editor's domain.
  $form['domain_form'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain node editing'),
    '#collapsible' => TRUE
  );
  $form['domain_form']['intro'] = array('#markup' => t('<p>When editors view domain-access restricted nodes, which form elements should be exposed?</p>'));
  $options = array(
    'log' => t('Log messages'),
    'options' => t('Publishing options'),
    'comment_settings' => t('Comment settings'),
    'path' => t('Path aliasing'),
  );
  ksort($options);
  $form['domain_form']['domain_form_elements'] = array(
    '#type' => 'checkboxes',
    '#default_value' => variable_get('domain_form_elements', array('options', 'delete', 'comment_settings', 'path')),
    '#options' => $options,
    '#description' => t('Some elements may not be editable for all users due to permission restrictions.'),
  );
  return system_settings_form($form);
}

/**
 * Checks to see if the webserver returns a valid response
 * for a request to a domain.
 *
 * @param $domain
 * An array containing the record from the {domain} table
 */
function domain_check_response($domain) {
  $url = domain_get_path($domain) . drupal_get_path('module', 'domain') . '/tests/200.png';
  $response = drupal_http_request($url);
  if ($response->code != 200) {
    drupal_set_message(t('%server is not responding as expected and may not be configured correctly at the server level.
      Server code !code was returned.',
    array('%server' => $url, '!code' => $response->code)), 'warning');
  }
}

/**
 * Allows for the batch update of certain elements.
 *
 * @param $action
 *   The name of the action to perform; corresponds to the keys of the $batch array
 *   returned by hook_domainbatch().
 * @return
 *   The appropriate form, or a list of actions, or an error.
 */
function domain_batch($action = NULL) {
  // We must have the module configured correctly.
  $domains = domain_domains();
  if (empty($domains)) {
    return t('There are no domains configured for this site.');
  }

  $batch = module_invoke_all('domainbatch');
  // We must have some actions, otherwise, no point.
  if (empty($batch)) {
    return t('There are no batch actions configured.');
  }

  // If we are on the main page, just list the actions.
  if (empty($action)) {
    return domain_batch_list($batch);
  }

  // If #variable is not set, eliminate the root domain.
  if (empty($batch[$action]['#variable'])) {
    unset($domains[0]);
  }
  // If we are doing a delete action, only valid domains can be acted upon.
  $allowed = array();
  if (!empty($batch[$action]['#table'])) {
    $table = db_escape_table($batch[$action]['#table']);
    $result = db_query("SELECT domain_id FROM {$table}");
    foreach ($result as $test) {
      $allowed[] = $domains[$test->domain_id];
    }
    if (empty($allowed)) {
      return t('There are no valid domains on which to perform this action.  The likely reason is that no records exist in the specified table.');
    }
  }
  else {
    $allowed = $domains;
  }
  // If we passed all the checks, generate the form.
  return drupal_get_form('domain_batch_form', $action, $batch[$action], $allowed);
}

/**
 * Lists available batch updates for the domain module.
 *
 * @param $batch
 *   An array of batch actions, as defined by hook_domainbatch().
 * @return
 *   A themed table of links and descriptions for batch actions.
 */
function domain_batch_list($batch) {
  $build = array();
  $header = array(t('Action'), t('Description'));
  $rows = array();
  foreach ($batch as $key => $value) {
    if (!isset($value['#module'])) {
      $value['#module'] = t('Other');
    }
  }
  uasort($batch, 'domain_batch_sort');
  $group = '';
  
  foreach ($batch as $key => $value) {
    $permission = isset($value['#permission']) ? $value['#permission'] : 'administer domains';
    if (!user_access($permission)) {
      continue;
    }
    if ($group != $value['#module']) {
      $rows[] = array(array('data' => '<strong>' . t('%module options', array('%module' => $value['#module'])) . '</strong>', 'colspan' => 2));
      $group = $value['#module'];
    }
    $rows[] = array(l($value['#form']['#title'], 'admin/structure/domain/batch/' . $key), $value['#meta_description']);
  }
  $output = '<p>' . t('Batch updates allow you to edit values for multiple domains at one time.  These functions are helpful when moving your sites from staging to production, or any time you need to make mass changes quickly.  The following batch update actions may be performed.') . '</p>';
  $output .= '<p><em>' . t('Note that you will only be shown domains on which each action may be performed.  If the module is not yet configured, some actions may be empty.') . '</em></p>';
  // Return the build array.
  $build['header'] = array(
    '#markup' => $output,
  );
  $build['content'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' =>  array('id' => 'domain-list'),
  );
  return $build;
}

/**
 * Sorting function for domain batch options.
 */
function domain_batch_sort($a, $b) {
  if ($a['#module'] != $b['#module']) {
    return strcmp($a['#module'], $b['#module']);
  }
  if ($a['#weight'] == $b['#weight']) {
    return strcmp($a['#form']['#title'], $b['#form']['#title']);
  }
  else {
    return ($a['#weight'] < $b['#weight']) ? -1 : 1;
  }
}

/**
 * Generate the form data for a batch update.
 *
 * @param $form_state
 *   The current form state, passed by FormsAPI.
 * @param $action
 *   The name of the action to perform; corresponds to the keys of the $batch array
 *   returned by hook_domainbatch().
 * @param $batch
 *   The batch data for this $action, as defined by hook_domainbatch().
 * @param $domains
 *   The current settings for each domain.
 * @return
 *   A themed table of links and descriptions for batch actions.
 */
function domain_batch_form($form, &$form_state, $action, $batch, $domains) {
  $default = array();
  drupal_set_title($batch['#form']['#title']);

  $form = array();
  $form['message'] = array(
    '#markup' => theme('domain_batch_title', array('batch' => $batch))
  );

  // For some values, allow every record to be updated.
  if (!empty($batch['#update_all'])) {
    $form['domain_batch_all'] = array(
      '#type' => 'fieldset',
      '#title' => t('Update value for all domains'),
      '#weight' => -10,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t('By entering a value below and checking the <strong>Apply to all domains</strong> option, you can set the desired value for all domains.'),
    );
    $form['domain_batch_all']['batch_all_setting'] = $batch['#form'];
    $form['domain_batch_all']['batch_all_setting']['#required'] = FALSE;
    $form['domain_batch_all']['batch_all_setting']['#default_value'] = isset($batch['#system_default']) ? $batch['#system_default'] : NULL;
    $form['domain_batch_all']['batch_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Apply to all domains'),
    );
    $form['domain_batch_all']['submit'] = array('#type' => 'submit', '#value' => t('Update all domain settings'));
  }
  $form['domain_batch'] = array(
    '#tree' => TRUE,
    '#title' => $batch['#form']['#title'],
    '#description' => $batch['#meta_description']
  );
  if ($batch['#domain_action'] == 'domain_conf' && $batch['#form']['#type'] == 'select') {
    $batch['#form']['#options'] = array('domain-conf-ignore' => t('Use primary domain settings')) + $batch['#form']['#options'];
  }
  foreach ($domains as $domain) {
    // Set the current value according to the stored values.
    if (isset($domain[$action])) {
      $default[$domain['domain_id']] = $domain[$action];
    }
    if ($batch['#domain_action'] == 'domain_conf') {
      // Set the default value to the main settings.
      // In rare cases, variable_get() returns unusable data, so we override it.
      if (empty($batch['#override_default'])) {
        $default[$domain['domain_id']] = variable_get($action, $batch['#system_default']);
      }
      else {
        $default[$domain['domain_id']] = $batch['#system_default'];
      }
      // Check for domain-specific settings.
      $result = db_query("SELECT settings FROM {domain_conf} WHERE domain_id = :domain_id", array(':domain_id' => $domain['domain_id']))->fetchField();
      $settings = domain_unserialize($result);
      if (isset($settings[$action])) {
        $default[$domain['domain_id']] = $settings[$action];
      }
    }
    elseif ($batch['#domain_action'] == 'custom' && isset($batch['#lookup'])) {
      $default[$domain['domain_id']] = isset($batch['#system_default']) ? $batch['#system_default'] : NULL;
      $func = $batch['#lookup'];
      $setting = $func($domain);
      if ($setting != -1) {
        $default[$domain['domain_id']] = $setting;
      }
    }
    // Take the settings from the $batch array.
    $form['domain_batch'][$domain['domain_id']] = $batch['#form'];
    // Add the domain-specific elements.
    $form['domain_batch'][$domain['domain_id']]['#sitename'] = check_plain($domain['sitename']);
    $form['domain_batch'][$domain['domain_id']]['#subdomain'] = check_plain($domain['subdomain']);
    if (isset($default[$domain['domain_id']])) {
      $form['domain_batch'][$domain['domain_id']]['#default_value'] = $default[$domain['domain_id']];
    }
  }
  $api_keys = array('variable', 'table', 'data_type');
  // These are optional elements, only passed if present.
  foreach ($api_keys as $key) {
    if (isset($batch['#' . $key])) {
      $form[$key] = array('#type' => 'value', '#value' => $batch['#' . $key]);
    }
  }
  // Custom submit and validate handlers.
  foreach (array('submit', 'validate') as $key) {
    if (isset($batch['#' . $key])) {
      $form[$key . '_handler'] = array('#type' => 'value', '#value' => $batch['#' . $key]);
    }
  }
  $form['handler'] = array('#type' => 'value', '#value' => $batch['#domain_action']);
  $form['batch_item'] = array('#type' => 'value', '#value' => $action);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Update domain settings'));

  return $form;
}

/**
 * FormsAPI theming.
 */
function theme_domain_batch_form($variables) {
  $form = $variables['form'];
  $output = '';
  $output = drupal_render($form['message']);
  if (isset($form['domain_batch_all'])) {
    $output .= drupal_render($form['domain_batch_all']);
  }
  $header = array(t('Id'), t('Site name'), t('Setting'));
  $rows = array();
  foreach (element_children($form['domain_batch']) as $key) {
    $temp = $form['domain_batch'][$key]['#title'];
    unset($form['domain_batch'][$key]['#title']);
    $row = array($key, $form['domain_batch'][$key]['#sitename'] .'<div class="description">'. $form['domain_batch'][$key]['#subdomain'] .'</div>', drupal_render($form['domain_batch'][$key]));
    $rows[] = $row;
    $form['domain_batch'][$key]['#title'] = $temp;
  }
  $output .= drupal_render_children($form['domain_batch']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme function for displaying batch editing.
 *
 * @param $batch
 *   The batch function to be performed.
 */
function theme_domain_batch_title($variables) {
  $output = '';
  $output = '<p>' . $variables['batch']['#meta_description'] . '</p>';
  if (!empty($variables['batch']['#required'])) {
    $output .= '<p><em>' . t('All values are required.') . '</em></p>';
  }
  return $output;
}

/**
 * FormsAPI for validating batch form actions.
 */
function domain_batch_form_validate($form, &$form_state) {
  // Define the validation function and call it.
  if (isset($form_state['values']['validate_handler']) && function_exists($form_state['values']['validate_handler'])) {
    $form_state['values']['validate_handler']($form_state['values']);
  }
}

/**
 * FormsAPI for saving batch form actions.
 */
function domain_batch_form_submit($form, &$form_state) {
  if (!empty($form_state['values']['batch_override'])) {
    foreach ($form_state['values']['domain_batch'] AS $domain_id => $value) {
      $options_all[$domain_id] = $form_state['values']['batch_all_setting'];
    }
    $form_state['values']['domain_batch'] = $options_all;
  }

  $item = $form_state['values']['batch_item'];
  switch ($form_state['values']['handler']) {
    case 'domain':
      foreach ($form_state['values']['domain_batch'] as $domain_id => $value) {
        db_update('domain')
          ->fields(array($item => $value))
          ->condition('domain_id', $domain_id)
          ->execute();
        // Update the variable for the root domain
        if (!empty($form_state['values']['variable']) && $domain_id == 0) {
          variable_set($form_state['values']['variable'], $value);
        }
      }
      break;
    case 'domain_conf':
      foreach ($form_state['values']['domain_batch'] as $domain_id => $value) {
        $settings = array();
        if ($domain_id > 0 || empty($form_state['values']['variable'])) {
          domain_conf_variable_save($domain_id, $item, $value);
        }
        elseif (!empty($form_state['values']['variable'])) {
          variable_set($form_state['values']['variable'], $value);
        }
      }
      break;
    case 'domain_delete':
      $table = $form_state['values']['table'];
      foreach ($form_state['values']['domain_batch'] as $key => $value) {
        if ($value == 1) {
          if (is_array($table)) {
            foreach ($table as $current) {
              db_delete($current)
                ->condition('domain_id', $key)
                ->execute();
            }
          }
          else {
            db_delete($table)
              ->condition('domain_id', $key)
              ->execute();
          }
        }
      }
      break;
    case 'custom':
      if (isset($form_state['values']['submit_handler']) && function_exists($form_state['values']['submit_handler'])) {
        $func = $form_state['values']['submit_handler'];
        $func($form_state['values']);
      }
      break;
  }
  drupal_set_message(t('Settings have been updated successfully.'), 'status', FALSE);
}

/**
 * FormsAPI to set default domain membership for each role.
 *
 * These settings are added to the $user object.
 *
 * @see domain_get_user_domains()
 */
function domain_roles_form($form, &$form_state) {
  $form = array();
  $form['domain_add_roles'] = array(
    '#type' => 'radios',
    '#options' => array(
      0 => t('Add default roles dynamically'),
      1 => t('Add default roles to the user account'),
    ),
    '#title' => t('Role settings behavior'),
    '#description' => t('Adding role settings to the user account will permanently save them for each user on account updates. Otherwise, role-based settings can be added or removed at will.'),
    '#default_value' => variable_get('domain_add_roles', 0),
  );

  $roles = user_roles();
  $defaults = variable_get('domain_roles', array());
  $roles[0] = t('new user');
  ksort($roles);

  $form['domain_roles'] = array(
    '#tree' => TRUE,
    '#value' => '<p>' . t('You may set optional default domains for each site user role. These settings will be added to each user when determining the permissions the user has to view and edit content on your site. These settings are expressly needed if you allow <em>anonymous users</em> to post content on your site.') . '</p>',
  );
  $domains = array();
  foreach (domain_domains() as $key => $value) {
    if ($key > 0) {
      $domains[$key] = $value['sitename'];
    }
    else {
      $domains[-1] = $value['sitename'];
    }
  }
  foreach ($roles as $rid => $role) {
    $form['domain_roles'][$rid]['#tree'] = TRUE;
    foreach ($domains as $domain_id => $domain) {
      $form['domain_roles'][$rid][$domain_id] = array(
        '#title' => filter_xss_admin($domain),
        '#type' => 'checkbox',
        '#default_value' => (isset($defaults[$rid][$domain_id])) ? $defaults[$rid][$domain_id] : 0,
      );
    }
  }

  $form = system_settings_form($form);
  // System settings form adds a theme we cannot use.
  unset($form['#theme']);
  return $form;
}

/**
 * FormsAPI theming.
 */
function theme_domain_roles_form($variables) {
  $form = $variables['form'];
  $output = '';
  $header = array(t('Domains'));
  $rows = array();
  $roles = user_roles();
  $roles[0] = t('new user');
  ksort($roles);
  $domains = domain_domains();
  foreach ($roles as $rid => $role) {
    $header[] = $role;
  }
  foreach ($domains as $domain_id => $domain) {
    $row = array();
    if ($domain_id == 0) {
      $domain_id = -1;
    }
    $row[] = filter_xss_admin($domain['sitename']);
    foreach ($roles as $rid => $role) {
      $form['domain_roles'][$rid][$domain_id]['#title'] = '';
      $row[] = drupal_render($form['domain_roles'][$rid][$domain_id]);
    }
    $rows[] = $row;
  }
  $output .= drupal_render($form['domain_add_roles']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}
