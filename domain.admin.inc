<?php

/**
 * @file
 * Administration functions for the domain module.
 *
 * These functions only need to be accessed from admin/structure/domain, so we put them in a separate include file.
 * @ingroup domain
 */

/**
 * Create an overview form for sorting domains and other quick actions.
 */
function domain_overview_form($form, &$form_state) {
  $check = (bool) db_query("SELECT COUNT(domain_id) FROM {domain}")->fetchField();
  if (empty($check)) {
    domain_set_primary_domain();
  }
  // Check for the 7.x.3 update.
  domain_check_for_update();
  // Set the form.
  $default = domain_default();
  $active_domain = domain_get_domain();
  $form = array();

  $form['top'] = array(
    '#markup' => t('<p>The following domains have been created for your site.  The currently active domain <strong>is shown in boldface</strong>.  You
                    may click on a domain to change the currently active domain.  Your default domain is %name, which will be used for all requests that fail to resolve to a registered domain.</p>', array('%name' => $default['subdomain'])),
  );
  // Cannot use domain_domains() here because we need to sort the output.
  $domains = array();
  $header = array(
    array('data' => t('Order')),
    array('data' => t('Weight')),
    array('data' => t('Name')),
    array('data' => t('Domain')),
    array('data' => t('Id')),
    array('data' => t('Active')),
    array('data' => t('Default')),
    array('data' => t('Operations')),
  );
  // Set up the base query.
  $limit = variable_get('domain_list_size', DOMAIN_LIST_SIZE);
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  $query = db_select('domain', 'd')
    ->fields('d', array('domain_id', 'sitename', 'subdomain', 'scheme', 'valid', 'weight', 'is_default'))
    ->orderBy('weight')
    ->extend('PagerDefault')
    ->limit($limit);
  // Get the domains.
  $result = $query->execute();
  while ($domain = $result->fetchAssoc()) {
    $domains[$domain['domain_id']] = domain_api($domain);
  }
  if (empty($domains)) {
    $form['error'] = array(
      '#markup' => t('No domains have been configured. <a href="!url">Add a new domain</a>.', array('!url' => url('admin/structure/domain/create'))),
    );
    return $form;
  }
  // Get the count of all domains, which may be paginated.
  $delta = count(domain_domains());
  $form['domain']['#tree'] = TRUE;
  $form['header'] = array(
    '#type' => 'value',
    '#value' => $header,
  );
  $options = array();
  $i = -1;
  // Now build the form elements.
  foreach ($domains as $domain_id => $domain) {
    $options[$domain_id] = '';
    $form['domain'][$domain_id]['values'] = array(
      '#type' => 'value',
      '#value' => $domain,
    );
    $form['domain'][$domain_id]['weight'] = array(
      '#type' => 'weight',
      '#default_value' => ($page * $limit) + $i++,
      '#delta' => $delta,
      '#attributes' => array('class' => array('domain-weight')),
    );
    $form['domain'][$domain_id]['sitename'] = array(
      '#type' => 'markup',
      '#markup' => ($active_domain['domain_id'] == $domain_id) ? '<strong>' . check_plain($domain['sitename']) . '</strong>' : check_plain($domain['sitename']),
    );
    $form['domain'][$domain_id]['subdomain'] = array(
      '#type' => 'markup',
      '#markup' => ($active_domain['domain_id'] == $domain_id) ? '<strong>' . l($domain['subdomain'], domain_get_uri($domain)) . '</strong>' : l($domain['subdomain'], domain_get_uri($domain)),
    );
    $form['domain'][$domain_id]['domain_id'] = array(
      '#type' => 'markup',
      '#markup' => check_plain($domain_id),
    );
    $form['domain'][$domain_id]['valid'] = array(
      '#type' => 'checkbox',
      // '#title' => !empty($domain['valid']) ? t('Active') : t('Inactive'),
      '#default_value' => !empty($domain['valid']),
    );
    $form['domain_actions'][$domain_id] = array(
      '#type' => 'markup',
      '#markup' => l(t('edit domain'), 'admin/structure/domain/view/' . $domain_id . '/edit'),
    );
  }
  $form['default_domain'] = array(
    '#type' => 'radios',
    '#options' => $options,
    '#default_value' => $default['domain_id'],
  );
  $form['pager'] = array(
    '#markup' => theme('pager'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  return $form;
}

/**
 * Validate handler for the domain overview form.
 */
function domain_overview_form_validate($form, &$form_state) {
  $values = $form_state['values']['domain'];
  $default = $form_state['values']['default_domain'];
  if (isset($values[$default])) {
    $domain = $values[$default]['values'];
    $error = domain_check_response($domain);
    if ($error) {
      $error = t('%domain may not be set as your primary domain.', array('%domain' => domain_get_path($domain))) . '<br />' . $error;
      form_set_error('default_domain', $error);
    }
  }
}

/**
 * Submit handler for the domain overview form.
 */
function domain_overview_form_submit($form, &$form_state) {
  $values = $form_state['values']['domain'];

  foreach ($values as $domain_id => $value) {
    $default = 0;
    $valid = $value['valid'];
    // Process the default domain.
    if ($form_state['values']['default_domain'] == $domain_id) {
      $default = 1;
      $valid = 1;
      if ($domain_id != $form_state['complete form']['default_domain']['#default_value']) {
        drupal_set_message(t('The primary domain has been set to %domain.', array('%domain' => domain_get_path($value['values']))));
      }
    }
    db_update('domain')
      ->fields(array('weight' => $value['weight'], 'is_default' => $default, 'valid' => $valid))
      ->condition('domain_id', $domain_id)
      ->execute();
    }

  drupal_set_message(t('Domain settings updated'));
}

/**
 * Module settings and behaviors.
 */
function domain_configure() {
  if (empty($_POST)) {
    // Is the module installed correctly?
    module_invoke_all('domain_install');
  }
  // Return the configuration form.
  return drupal_get_form('domain_configure_form');
}

/**
 * FormsAPI for configuring the domain module.
 */
function domain_configure_form($form, &$form_state, $user_submitted = FALSE) {
  $form['domain_sitename_override'] = array(
    '#type' => 'checkbox',
    '#title' => t('Override site name with name of domain.'),
    '#default_value' => variable_get('domain_sitename_override', 1),
    '#description' => t('When enabled, the site name is overridden with the domain name set on the domain edit page.'),
  );
  $form['domain_debug'] = array(
    '#type' => 'radios',
    '#title' => t('Debugging status'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_debug', 0),
    '#options' => array(0 => t('Do not show debugging output'), 1 => t('Show debugging output on node view')),
    '#description' => t('If set, users with the <em>set domain access</em> permission will be able to view the node access rules for each node. See the README for more details.')
  );

  $form['domain_force_admin'] = array(
    '#type' => 'radios',
    '#title' => t('Enforce rules on administrators'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_force_admin', 0),
    '#options' => array(0 => t('Do not enforce'), 1 => t('Restrict node views for administrators')),
    '#description' => t('If set, users with the <em>administer nodes</em> permission and user 1 <em>will view the site with Domain Access restrictions enforced</em>. See the README for more details.')
  );
  $form['domain_list_size'] = array(
    '#type' => 'select',
    '#title' => t('Domain list size'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_list_size', DOMAIN_LIST_SIZE),
    '#options' => drupal_map_assoc(array(5, 10, 20, 25, 30, 40, 50, 75, 100, 150, 200, 250, 500, 750, 1000)),
    '#description' => t('Sets a break point for the size of domain lists shown to users. After this point, user interfaces will use tables, pagination, and select lists to prevent too many domains from appearing in a list. <em>Note: setting this value higher than 200 may cause memory and display issues for your site.</em>')
  );
  $form['domain_vertical_tab'] = array(
    '#type' => 'radios',
    '#title' => t('Display in vertical tabs'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_vertical_tab', 0),
    '#options' => array(t('No'), t('Yes')),
    '#description' => t('Present the domain form options in a vertical tab.')
  );
  $form['domain_select_format'] = array(
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
    '#collapsed' => TRUE,
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

  $form['domain_advanced']['domain_edit_on_primary'] = array(
    '#type' => 'checkbox',
    '#title' => t('Force domain editing from the primary domain'),
    '#default_value' => variable_get('domain_edit_on_primary', 1),
    '#description' => t('When editing a domain, you will be redirected to the primary domain. Doing so ensures that
      the server can handle changes to the domain record. <br /><em>Disabling this feature may make development environments
      easier to support. Use at your own risk.</em>'),
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

  $form['domain_classes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain-specific CSS classes'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $form['domain_classes']['domain_classes'] = array(
    '#type' => 'textarea',
    '#rows' => 5,
    '#cols' => 40,
    '#default_value' => variable_get('domain_classes', 'domain-[current-domain:machine_name]'),
  );
  if (module_exists('token')) {
    $form['domain_classes']['domain_classes']['#description'] = t('Add the following classes to the <em>body</em> tag of every page. Enter one class per line. This field supports tokens.');
    $form['domain_classes']['token_help'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('current-domain'),
      '#global_types' => FALSE,
    );
  }
  else {
    $tokens = domain_get_tokens(array('current-domain'));
    $form['domain_classes']['domain_classes']['#description'] = t('Add the following classes to the <em>body</em> tag of every page. Enter one class per line. The following token replacements are allowed: !tokens', array('!tokens' => theme('item_list', array('items' => $tokens))));
  }

  // Allow submodules to add elements to the form.
  $modules = module_implements('domain_form');
  if (!empty($modules)) {
    foreach ($modules as $module) {
      $func = $module . '_domain_form';
      $func($form);
    }
  }
  return system_settings_form($form);
}

/**
 * Return an array of values suitable for both create and update sql statements
 *
 * The last element on this array is ignored by the create sql because it has
 * one fewer placeholder argument as it doesn't require a WHERE clause.
 *
 * @param $form_state
 *   From state from the submit_hook
 *
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
    'weight' => $form_state['values']['weight'],
    'is_default' => $form_state['values']['is_default'],
    'machine_name' => $form_state['values']['machine_name'],
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
  $error = FALSE;
  if (!isset($arguments['ignore_domain_status_check'])) {
    if (variable_get('domain_edit_on_primary', 1)) {
      domain_check_primary();
    }
    if (!empty($domain)) {
      $error = domain_check_response($domain);
      if ($error && empty($_POST)) {
        drupal_set_message($error, 'warning', FALSE);
      }
    }
  }
  // Ensure indexes are set.
  foreach ($_domain as $key => $value) {
    if (!isset($domain[$key])) {
      $domain[$key] = NULL;
    }
  }
  $form = array();
  drupal_set_title(t('Edit !domain', array('!domain' => $domain['subdomain'])));
  $form['#domain'] = $domain;
  // The $arguments array allows other modules to pass values to change the behavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  if (!variable_get('domain_allow_non_ascii', FALSE)) {
    $description = t('Must contain only dots, lowercase alphanumeric characters, dashes and a colon (if using alternative ports).');
  }
  else {
    $description = t('Must contain only dots, lowercase alphanumeric and ASCII characters, dashes and a colon (if using alternative ports).');
  }
  $form['subdomain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => $domain['subdomain'],
    '#description' => t('The allowed domain, using the full <em>path.example.com</em> format.') . '<br />' . t('Leave off the http:// and the trailing slash and do not include any paths.') . '<br />' . $description
  );
  $form['machine_name'] = array(
    '#type' => 'machine_name',
    '#machine_name' => array(
      'source' => array('subdomain'),
      'exists' => 'domain_check_machine_name',
    ),
    '#default_value' => $domain['machine_name'],
  );
  $form['sitename'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => $domain['sitename'],
    '#description' => t('The human-readable name of this domain.')
  );
  $form['scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Domain URL scheme'),
    '#required' => TRUE,
    '#options' => array('http' => 'http://', 'https' => 'https://'),
    '#default_value' => !empty($domain['scheme']) ? $domain['scheme'] : 'http',
    '#description' => t('The URL scheme for accessing this domain.')
  );
  $form['valid'] = array(
    '#type' => 'radios',
    '#title' => t('Domain status'),
    '#required' => TRUE,
    '#options' => array(1 => t('Active'), 0 => t('Inactive')),
    '#default_value' => isset($domain['valid']) ? $domain['valid'] : 1,
    '#description' => t('Must be set to "Active" for users to navigate to this domain.')
  );
  $next_weight = 0;
  foreach (domain_domains() as $record) {
    if ($record['weight'] > $next_weight) {
      $next_weight = $record['weight'];
    }
  }
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#required' => TRUE,
    '#delta' => count(domain_domains()) + 1,
    '#default_value' => isset($domain['weight']) ? $domain['weight'] : $next_weight + 1,
    '#description' => t('The sort order for this record. Lower values display first.'),
  );
  $form['is_default'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default domain'),
    '#default_value' => isset($domain['is_default']) ? $domain['is_default'] : 0,
    '#description' => t('If a URL request fails to match a domain record, the settings for this domain will be used.'),
  );
  // If the server did not respond properly, do not allow this record to be set
  // as the default domain.
  if ($error) {
    $form['is_default']['#description'] .= '<br />' . t('<strong>This domain did not respond correctly. It cannot be set as your primary domain unless you select <em>Ignore server response warning</em></strong>.');
    $form['ignore'] = array(
      '#type' => 'checkbox',
      '#title' => t('Ignore server response warning'),
      '#default_value' => 0,
    );
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save domain record'), '#prefix' => '<br />');
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
  $domain_changed = (bool) strcmp($form_state['values']['subdomain'], $form['subdomain']['#default_value']);
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
  if (!empty($form_state['values']['is_default']) && empty($form_state['values']['ignore'])) {
    $error = domain_check_response($form_state['values']);
    if ($error) {
      $error = t('This domain may not be set as your primary domain.') . '<br />' . $error;
      form_set_error('is_default', $error);
    }
  }
}

/**
 * Implement domain_form submit hook.
 */
function domain_form_submit($form, &$form_state) {
  $values = domain_values_from_form_state($form_state);
  // Set the proper message.
  if (!empty($values['domain_id']) || $values['domain_id'] == 0) {
    $message = t('Domain record updated.');
  }
  else {
    $message = t('Domain record created.');
  }

  if (!empty($values['is_default'])) {
    $message .= ' ' . t('Set as primary domain.');
  }

  // Run the save routine.
  $domain = domain_save($values, $form_state);

  // If return is not a $domain array, something went wrong.
  if ($domain == -1) {
    $message = t('Domain record failed.');
  }
  else {
    // Update the domain_id in $form_state for any submit handlers that need it.
    if (empty($form_state['values']['domain_id'])) {
      $form_state['values']['domain_id'] =  $domain['domain_id'];
    }
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
  drupal_set_title(t('Delete !domain', array('!domain' => $domain['sitename'])));
  $form = array();
  // The $arguments array allows other modules to pass values to change the behavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain'] = array('#type' => 'value', '#value' => $domain);

  if (!empty($domain['is_default'])) {
    drupal_set_message(t('The primary domain may not be deleted. <a href="!url">Select a new primary domain</a> before deleting this record.', array('!url' => url('admin/structure/domain'))), 'warning');
    return $form;
  }

  // Allow for data resets.
  $domains = domain_domains();
  $options = array('none' => t('Do not reassign'));
  foreach ($domains as $record) {
    if ($record['domain_id'] != $domain['domain_id']) {
      $options[$record['domain_id']] = $record['sitename'];
    }
  }
  $form['domain_access'] = array(
    '#title' => t('Reassign content'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => domain_default_id(),
    '#description' => t('All content assigned to %domain will be re-assigned to your selection.', array('%domain' => $domain['sitename'])),
  );
  $form['domain_editor'] = array(
    '#title' => t('Reassign editors'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => domain_default_id(),
    '#description' => t('All users assigned to %domain will be re-assigned to your selection.', array('%domain' => $domain['sitename'])),
  );

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
 * Allows for the batch update of certain elements.
 *
 * @param $action
 *   The name of the action to perform; corresponds to the keys of the $batch array
 *   returned by hook_domain_batch().
 *
 * @return
 *   The appropriate form, or a list of actions, or an error.
 */
function domain_batch($action = NULL) {
  // We must have the module configured correctly.
  $domains = domain_domains();
  if (empty($domains)) {
    return t('There are no domains configured for this site.');
  }

  $batch = domain_batch_actions();
  // We must have some actions, otherwise, no point.
  if (empty($batch)) {
    return t('There are no batch actions configured.');
  }

  // If we are on the main page, just list the actions.
  if (empty($action)) {
    return domain_batch_list($batch);
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
 *   An array of batch actions, as defined by hook_domain_batch().
 *
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
 *   returned by hook_domain_batch().
 * @param $batch
 *   The batch data for this $action, as defined by hook_domain_batch().
 * @param $domains
 *   The current settings for each domain.
 *
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
      if (is_array($default[$domain['domain_id']])) {
        foreach ($default[$domain['domain_id']] as $fieldname => $value) {
          $form['domain_batch'][$domain['domain_id']][$fieldname]['#default_value'] = $value;
        }
      }
      else {
        $form['domain_batch'][$domain['domain_id']]['#default_value'] = $default[$domain['domain_id']];
      }
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
  $header = array(t('Id'), t('Domain'), t('Setting'));
  $rows = array();
  foreach (element_children($form['domain_batch']) as $key) {
    $temp = $form['domain_batch'][$key]['#title'];
    unset($form['domain_batch'][$key]['#title']);
    $row = array($key, $form['domain_batch'][$key]['#sitename'] . '<div class="description">' . $form['domain_batch'][$key]['#subdomain'] . '</div>', drupal_render($form['domain_batch'][$key]));
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
 * FormAPI to set default domain settings per content type.
 */
function domain_nodes_form($form, &$form_state) {
  $form = array();

  $node_types = node_type_get_names();
  $options = array(DOMAIN_ALL => t('All domains'), DOMAIN_ACTIVE => t('Author\'s currently active domain'));
  foreach (domain_domains() as $key => $value) {
    $options[$value['machine_name']] = $value['sitename'];
  }

  foreach ($options as $key => $value) {
    $form['#domain_node'][$key] = $value;
  }

  foreach ($node_types as $type => $name) {
    $default_values = domain_default_node_access_settings($type);
    $form['domain_node']['domain_node_' . $type] = array(
      '#type' => 'checkboxes',
      '#tree' => TRUE,
      '#options' => $options,
      '#default_value' => $default_values,
    );
  }

  $form = system_settings_form($form);
  // System settings form adds a theme we cannot use.
  unset($form['#theme']);
  return $form;
}

/**
 * FormsAPI theming.
 */
function theme_domain_nodes_form($variables) {
  $form = $variables['form'];
  $output = '<p>' . t('You may set default domain publishing options to the content types when new content will be created. When you publish to <em>All domains</em>, you can additionally define domain memberships.') . '</p>';
  $num_columns = 15;
  $node_types = node_type_get_names();
  $num_node_types = count($node_types);

  for ($i = 0; $i < $num_node_types; $i += $num_columns) {
    $header = array(t('Domains'));
    $rows = array();
    $count = 0;
    $used_node_types = array();
    foreach ($node_types as $type => $name) {
      if ($count == $num_columns) {
        break;
      }
      $header[] = $name;
      $used_node_types[$type] = $name;
      array_shift($node_types);
      $count++;
    }
    foreach ($form['#domain_node'] as $key => $sitename) {
      $row = array();
      $row[] = filter_xss_admin($sitename);
      foreach ($used_node_types as $type => $name) {
        $form['domain_node']['domain_node_' . $type][$key]['#title'] = '';
        $row[] = drupal_render($form['domain_node']['domain_node_' . $type][$key]);
      }
      $rows[] = $row;
    }
    $output .= theme('table', array('header' => $header, 'rows' => $rows));
  }

  $output .= drupal_render_children($form);
  return $output;
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
    $domains[$value['machine_name']] = $value['sitename'];
  }
  foreach ($roles as $rid => $role) {
    $form['domain_roles'][$rid]['#tree'] = TRUE;
    $title = t('All domains');
    $form['#domain_roles'][DOMAIN_ALL] = $title;
    $form['domain_roles'][$rid][DOMAIN_ALL] = array(
      '#title' => $title,
      '#type' => 'checkbox',
      '#default_value' => (isset($defaults[$rid][DOMAIN_ALL])) ? $defaults[$rid][DOMAIN_ALL] : 0,
    );
    foreach ($domains as $machine_name => $domain) {
      $form['#domain_roles'][$machine_name] = $domain;
      $form['domain_roles'][$rid][$machine_name] = array(
        '#title' => filter_xss_admin($domain),
        '#type' => 'checkbox',
        '#default_value' => (isset($defaults[$rid][$machine_name])) ? $defaults[$rid][$machine_name] : 0,
        '#states' => array(
          'disabled' => array(
            'input[name="domain_roles[' . $rid . '][' . DOMAIN_ALL . ']"]' => array('checked' => TRUE),
          ),
        ),
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
  foreach ($form['#domain_roles'] as $machine_name => $title) {
    $row = array();
    $row[] = isset($domains[$machine_name]) ? filter_xss_admin($domains[$machine_name]['sitename']) : $title;
    foreach ($roles as $rid => $role) {
      $form['domain_roles'][$rid][$machine_name]['#title'] = '';
      $row[] = drupal_render($form['domain_roles'][$rid][$machine_name]);
    }
    $rows[] = $row;
  }
  $output .= drupal_render($form['domain_add_roles']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme the domain overview form.
 */
function theme_domain_overview_form($variables) {
  $form = $variables['form'];
  if (!empty($form['error'])) {
    $output = drupal_render($form['error']);
    return $output;
  }
  // Add table javascript.
  drupal_add_js('misc/tableheader.js');
  drupal_add_tabledrag('domains', 'order', 'sibling', 'domain-weight');

  $header = $form['header']['#value'];
  $output = '';
  $output .= drupal_render($form['top']);
  $rows = array();
  foreach (element_children($form['domain']) as $key) {
    $row = array();
    foreach (element_children($form['domain'][$key]) as $item) {
      $row[] = array(
        'data' => drupal_render($form['domain'][$key][$item]),
        'class' => array($item),
      );
    }
    $row[] = drupal_render($form['default_domain'][$key]);
    $row[] = drupal_render($form['domain_actions'][$key]);
    $rows[] = array(
      'data' => $row,
      'class' => array('draggable'),
    );
  }
  $attributes = array(
    'id' => 'domains',
    'sticky' => TRUE,
  );
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => $attributes));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Repairs and updates instances of domain_id 0 after the update to 7.x.3.
 */
function domain_repair_form($form, &$form_state) {
  $list = domain_update_module_check();
  if (empty($list)) {
    $form['list'] = array(
      '#markup' => '<p>' . t('No updates are required. <a href="!url">Return to domain administration</a>.', array('!url' => url('admin/structure/domain'))) . '</p>',
    );
  }
  else {
    $form['intro'] = array(
      '#markup' => '<p>' . t('The following modules have data that references domain_id 0 and will be updated:') . '</p>',
    );
    $modules = array();
    foreach ($list as $item) {
      $modules[] = check_plain($item['name']);
    }
    $form['list'] = array(
      '#markup' => theme('item_list', array('items' => $modules)),
    );
    $form['outro'] = array(
      '#markup' => '<p>' . t('All records marked with domain_id 0 will be assigned to the default domain.') . '</p>',
    );
    $form['tables'] = array(
      '#type' => 'value',
      '#value' => domain_update_tables($list),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update database tables'),
    );
  }
  return $form;
}

/**
 * Repairs references to domain_id 0.
 */
function domain_repair_form_submit($form, &$form_state) {
  $tables = $form_state['values']['tables'];
  $success = domain_update_zero_records($tables);
  if ($success) {
    drupal_set_message(t('!count database table(s) were updated.', array('!count' => count($form_state['values']['tables']))));
  }
  $form_state['redirect'] = 'admin/structure/domain';
}
