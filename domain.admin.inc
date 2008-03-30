<?php
// $Id$

/**
 * @file
 * Administration functions for the domain module.
 *
 * These functions only need to be accessed from admin/build/domain, so we put them in a separate include file.
 * @ingroup domain
 */

/**
 * The main administration page, a list of active domains.
 *
 * @return
 *  A sortable table of active domain records, or an error message.
 */
function domain_view() {
  global $_domain;
  $output = t('<p>The following domains have been created for your site.  The currently active domain <b>is shown in boldface</b>.  You
                    may click on a domain to change the currently active domain.  Your default domain has an ID of zero (0) and is always
                    at the top of the list below.</p>');
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
  $domains[] = domain_default();

  // Get any select sql from other modules.
  $select = module_invoke_all('domainview', 'select');
  $select_sql = '';
  if (!empty($select)) {
    $select_sql = ', '. implode(', ', $select);
    $select_sql = rtrim($select_sql, ',');
  }

  // Get any tablesort sql from other modules.
  $join = module_invoke_all('domainview', 'join');
  $join_sql = '';
  if (!empty($join)) {
    $join_sql = ' '. implode(' ', $join);
  }

  $sql = 'SELECT d.*'. $select_sql .' FROM {domain} d'. $join_sql . tablesort_sql($header);
  $result = pager_query($sql, 24);
  while ($data = db_fetch_array($result)) {
    $domains[] = $data;
  }
  foreach ($domains as $domain) {
    // Let submodules overwrite the defaults, if they wish.
    $domain = domain_api($domain);
    $link = l($domain['subdomain'], domain_get_uri($domain), array('absolute' => TRUE));
    if ($domain['domain_id'] == 0) {
      $actions = l(t('Default settings'), 'admin/build/domain/settings');
      // Grab any extra elements defined by other modules.  If so, allow configuration.
      if (module_exists('domain_conf')) {
        $extra = array();
        $extra = domain_conf_api();
        if (!empty($extra)) {
          $actions .= ' | '. l(t('settings'), 'admin/build/domain/conf/0');
        }
      }
    }
    else {
      $actions = l(t('edit'), 'admin/build/domain/edit/'. $domain['domain_id']);
      // Add advanced settings from other modules.
      $items = array();
      $items = module_invoke_all('domainlinks', $domain);
      if (!empty($items)) {
        foreach ($items as $item) {
          $actions .= ' | '. l($item['title'], $item['path']);
        }
      }
      $actions .= ' | '. l(t('delete'), 'admin/build/domain/delete/'. $domain['domain_id']);
    }
    // Set the valid flag.
    ($domain['valid'] == 1) ? $valid = t('Active') : $valid = t('Inactive');
    $row = array($domain['domain_id'], ($domain['subdomain'] == $_domain['subdomain']) ? '<b>'. $link .'</b>' : $link, filter_xss_admin($domain['sitename']), $valid, $domain['scheme']);
    // Let other modules add data.
    foreach (module_implements('domainview') as $module) {
      $row[] = module_invoke($module, 'domainview', 'data', $domain);
    }
    // Add the actions.
    $row[] = $actions;
    $rows[] = $row;
  }
  if (!empty($rows)) {
    $output .= theme_table($header, $rows);
    $output .= theme('pager', NULL, 24, 0);
    return $output;
  }
  else {
    return t('No domains have been configured.');
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
function domain_configure_form($form_state, $user_submitted = FALSE) {
  $form = array();
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default domain settings'),
    '#collapsible' => TRUE
  );
  $sitename = variable_get('sitename', 'Drupal');
  $form['domain']['domain_root'] = array(
    '#type' => 'textfield',
    '#title' => t('Primary domain name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => variable_get('domain_root', ''),
    '#description' => t('The primary domain for your site.  Typically <em>example.com</em> or <em>www.example.com</em>.  Do not use http or slashes.
      <br />This domain will be used as the default URL for your site.
      <br />If an invalid domain is requested, users will be sent to the primary domain.')
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
    '#collapsed' => TRUE
  );

  $form['domain_behavior']['domain_behavior'] = array(
    '#type' => 'radios',
    '#title' => t('New content settings'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_behavior', DOMAIN_INSTALL_RULE),
    '#options' => array(1 => t('Show on all affiliate sites'), 2 => t('Only show on selected sites')),
    '#description' => t('If set, this value will automatically assign new content to all sites. <a href="!url">Node-type specific settings</a> are also available.', array('!url' => url('admin/build/domain/advanced')))
  );

  $form['domain_behavior']['domain_options'] = array(
    '#type' => 'radios',
    '#title' => t('Content editing forms'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_options', 0),
    '#options' => array(
      0 => t('Pass the default form values as hidden fields'),
      1 => t('Take user to the default domain'),
      2 => t('Take user to their assigned domain'),
      3 => t('Show user their publishing options')),
    '#description' => t('Controls the behavior of forms for users with the <em>view domain publishing</em> permission when creating or editing content. See the README for more details.')
  );

  $form['domain_behavior']['domain_debug'] = array(
    '#type' => 'radios',
    '#title' => t('Debugging status'),
    '#required' => TRUE,
    '#default_value' => variable_get('domain_debug', 0),
    '#options' => array(0 => t('Do not show debugging output'), 1 => t('Show debugging output on node view')),
    '#description' => t('If set, users with the <em>set domain access</em> permission will be able to view the node access rules for each node. See the README for more details.')
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

  $form['domain_advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );

  $form['domain_advanced']['domain_editors'] = array(
    '#type' => 'radios',
    '#title' => t('Domain-based editing controls'),
    '#default_value' => variable_get('domain_editors', intval(DOMAIN_EDITOR_RULE)),
    '#options' => array(0 => t('Do not use access control for editors'), 1 => t('Use access control for editors')),
    '#description' => t('If set, users with the <em>edit domain nodes</em> permission will be able to edit all nodes assigned to their domain.')
  );
  // When turning on and off editing rules, we rebuild part of the node_access table.
  $form['#submit'][] = 'domain_admin_editor_form_submit';
  $form['domain_editors_status'] = array('#type' => 'value', '#value' => variable_get('domain_editors', 0));

  // Check to see if the node_access patch is enabled.  If it is, then
  // we need to know if more than one node_access module is running.
  $modules = count(module_implements('node_grants'));

  if (!function_exists('node_access_grants_sql') ||  $modules < 2) {
    $disabled = TRUE;
    variable_set('domain_access_rules', 0);
  }
  // Do not show this form element unless it is necessary
  if ($modules > 1) {
    $form['domain_advanced']['domain_access_rules'] = array(
      '#type' => 'radios',
      '#title' => t('Node access settings'),
      '#disabled' => $disabled,
      '#default_value' => variable_get('domain_access_rules', 0),
      '#options' => array(0 => t('Use the default Drupal behavior (OR)'), 1 => t('Check Domain Access in addition to other modules (AND)')),
      '#description' => t('Controls how Domain Access interacts with access control modules such as Organic Groups. Requires the multiple_node_access patch.')
    );
  }
  // Check to see if the hook_url_alter() patch is installed.
  $disabled = FALSE;
  if (url('domain_access_test_path') != url('domain_access_path_test')) {
    $disabled = TRUE;
    variable_set('domain_search', 0);
    variable_set('domain_seo', 0);
  }
  $form['domain_advanced']['domain_search'] = array(
    '#type' => 'radios',
    '#title' => t('Search settings'),
    '#disabled' => $disabled,
    '#default_value' => variable_get('domain_search', 0),
    '#options' => array(0 => t('Search content for the current domain only'), 1 => t('Search all domains from any URL')),
    '#description' => t('Options for content searching.  This feature requires custom_url_rewrite_outbound() to be installed.')
  );

  $form['domain_advanced']['domain_seo'] = array(
    '#type' => 'radios',
    '#title' => t('Search engine optimization'),
    '#disabled' => $disabled,
    '#default_value' => variable_get('domain_seo', 0),
    '#options' => array(0 => t('Do not rewrite URLs'), 1 => t('Rewrite all URLs to point to a single source')),
    '#description' => t('If rewrite is turned on, all node links will point to a single instance of the node.  This
      option reduces the chance that search engines will recognize duplicate content.
      This feature requires custom_url_rewrite_outbound() to be installed.')
  );

  $form['domain_advanced']['domain_www'] = array(
    '#type' => 'radios',
    '#title' => t('WWW prefix handling'),
    '#default_value' => variable_get('domain_www', 0),
    '#options' => array(0 => t('Process all host requests normally'), 1 => t('Treat www.*.example.com as an alias of *.example.com')),
    '#description' => t('If set, calls to www.* will be treated as if the www. did not exist.')
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
  // Requires custom_url_rewrite_outbound() to be installed for these settings to be applied.
  if (!$disabled) {
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
      '#default_value' => variable_get('domain_paths', "node/%n\r\ncomment/reply/%n\r\nnode/add/book/parent/%n\r\nbook/export/html/%n"),
      '#description' => t('When using SEO or other path rewrites, the following link paths should be turned into absolute URLs.  Enter
        the Drupal path of the link, using the <em>%n</em> placeholder to represent the node id.
        Enter one path per line.  See the README for more details.')
    );
  }
  // Allow submodules to add elements to the form.
  $modules = module_implements('domainform');
  if (!empty($modules)) {
    foreach ($modules as $module) {
      $func = $module .'_domainform';
      $func($form);
    }
  }
  return system_settings_form($form);
}

/**
 * FormsAPI for enabling domain editors
 *
 * This function writes straight to the {node_access} table because we are
 * adding a grant in addition to one that already exists.
 */
function domain_admin_editor_form_submit($form, &$form_state) {
  $clear = FALSE;
  if ($form_state['values']['domain_editors_status'] == 0 && $form_state['values']['domain_editors'] == 1) {
    // Apply rules
    $result = db_query("SELECT nid, gid FROM {node_access} WHERE realm = '%s'", 'domain_id');
    while ($data = db_fetch_array($result)) {
      db_query("INSERT INTO {node_access} (nid, gid, realm, grant_view, grant_update, grant_delete) VALUES (%d, %d, '%s', %d, %d, %d)", $data['nid'], $data['gid'], 'domain_editor', 0, 1, 1);
      db_query("INSERT INTO {domain_access} (nid, gid, realm) VALUES (%d, %d, '%s')", $data['nid'], $data['gid'], 'domain_editor');
    }
    $clear = TRUE;
  }
  else if ($form_state['values']['domain_editors_status'] == 1 && $form_state['values']['domain_editors'] == 0) {
    // Remove rules
    db_query("DELETE FROM {node_access} WHERE realm = '%s'", 'domain_editor');
    db_query("DELETE FROM {domain_access} WHERE realm = '%s'", 'domain_editor');
    $clear = TRUE;
  }
  // We have reset the node_access table and need to clear the cache.
  if ($clear) {
    cache_clear_all();
  }
}

/**
 * Create a new domain record
 */
function domain_create() {
  return drupal_get_form('domain_create_form');
}

/**
 * FormsAPI for creating domain records.
 *
 * @param $arguments
 *  An array of additional hidden key/value pairs to pass to the form.
 *  Used by child modules to control behaviors.
 *  Currently supported arguments are:
 *    'user_submitted' => TRUE
 *    Indicates that a form should not process administrative messages and paths
 *    upon form submit.  Used by the Domain User module.
 */
function domain_create_form($form_state, $arguments = array()) {
  $form = array();
  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('New domain record'),
    '#collapsible' => TRUE
  );
  $form['domain']['subdomain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#description' => t('The allowed domain, using the full <em>path.example.com</em> format.  Can only contain lower-case alphanumeric characters.  Leave off the http:// and the trailing slash.')
  );
  $form['domain']['sitename'] = array(
    '#type' => 'textfield',
    '#title' => t('Site name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#description' => t('The human-readable name of this domain.')
  );
  $form['domain']['scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Domain URL scheme'),
    '#required' => TRUE,
    '#options' => array('http' => 'http://', 'https' => 'https://'),
    '#default_value' => 'http',
    '#description' => t('The URL scheme for accessing this domain.')
  );
  $form['domain']['valid'] = array(
    '#type' => 'radios',
    '#title' => t('Domain status'),
    '#required' => TRUE,
    '#options' => array(1 => t('Active'), 0 => t('Inactive')),
    '#default_value' => 1,
    '#description' => t('Must be set to "Active" for users to navigate to this domain.')
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Create domain record'));
  return $form;
}

/**
 * FormsAPI for domain_create_form()
 */
function domain_create_form_validate($form, &$form_state) {
  // TODO: Make this a proper regex?
  $subdomain = strtolower(urlencode($form_state['values']['subdomain']));
  $check = 0;
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE subdomain = '%s'", $subdomain));
  if ($check > 0) {
    form_set_error('subdomain', t('The subdomain value must be unique.'));
  }
  $check = 0;
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE sitename = '%s'", $form_state['values']['sitename']));
  if ($check > 0) {
    form_set_error('sitename', t('The site name value must be unique.'));
  }
}

/**
 * FormsAPI for domain_create_form()
 */
function domain_create_form_submit($form, &$form_state) {
  db_query("INSERT INTO {domain} (subdomain, sitename, scheme, valid) VALUES ('%s', '%s', '%s', %d)", $form_state['values']['subdomain'], $form_state['values']['sitename'], $form_state['values']['scheme'], $form_state['values']['valid']);
  // Let other modules act.
  $domain = domain_lookup(NULL, $form_state['values']['subdomain']);
  module_invoke_all('domainupdate', 'create', $domain, $form_state['values']);
  // The user_submitted flag is needed for Domain User.
  if (!$form_state['values']['domain_arguments']['user_submitted']) {
    drupal_set_message(t('Domain record created.'));
    $form_state['redirect'] = 'admin/build/domain/view';
  }
}

/**
 * Edit an existing domain record
 *
 * @param $domain
 *  The $domain object created by domain_lookup().
 */
function domain_edit($domain) {
  if ($domain == -1) {
    return t('Invalid page requested.');
  }
  // This action should be performed from the primary domain.
  domain_goto(domain_default());
  return drupal_get_form('domain_edit_form', $domain);
}

/**
 * FormsAPI for editing a domain record
 *
 * @param $form_state
 * The current form state, passed by FormsAPI.
 * @param $domain
 * An array containing the record from the {domain} table.
 * @param $arguments
 *  An array of additional hidden key/value pairs to pass to the form.
 *  Used by child modules to control behaviors.
 *  Currently supported arguments are:
 *    'user_submitted' => TRUE
 *    Indicates that a form should not process administrative messages and paths
 *    upon form submit.  Used by the Domain User module.
 */
function domain_edit_form($form_state, $domain, $arguments = array()) {
  if (empty($_POST)) {
    domain_check_response($domain);
  }
  $form = array();
  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Edit domain record'),
    '#collapsible' => TRUE
  );
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form['domain']['subdomain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => $domain['subdomain'],
    '#description' => t('The allowed domain, using the full <em>path.example.com</em> format.  Can only contain lower-case alphanumeric characters.  Leave off the http:// and the trailing slash.')
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
    '#default_value' => $domain['scheme'],
    '#description' => t('The URL scheme for accessing this domain.')
  );
  $form['domain']['valid'] = array(
    '#type' => 'radios',
    '#title' => t('Domain status'),
    '#required' => TRUE,
    '#options' => array(1 => t('Active'), 0 => t('Inactive')),
    '#default_value' => $domain['valid'],
    '#description' => t('Must be set to "Active" for users to navigate to this domain.')
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save domain record'));
  return $form;
}

/**
 * FormsAPI for domain_edit_form()
 */
function domain_edit_form_validate($form, &$form_state) {
  // TODO: Make this a proper regex
  $subdomain = strtolower(urlencode($form_state['values']['subdomain']));
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE subdomain = '%s' AND domain_id <> %d", $subdomain, $form_state['values']['domain_id']));
  if ($check) {
    form_set_error('subdomain', t('The subdomain value must be unique.'));
  }
  $check2 = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE sitename = '%s' AND domain_id <> %d", $form_state['values']['sitename'], $form_state['values']['domain_id']));
  if ($check2) {
    form_set_error('sitename', t('The site name value must be unique.'));
  }
}

/**
 * FormsAPI for domain_edit_form()
 */
function domain_edit_form_submit($form, &$form_state) {
  db_query("UPDATE {domain} SET subdomain = '%s', sitename = '%s', scheme = '%s', valid = %d WHERE domain_id = %d", $form_state['values']['subdomain'], $form_state['values']['sitename'], $form_state['values']['scheme'], $form_state['values']['valid'], $form_state['values']['domain_id']);
  // Let other modules act.
  $domain = domain_lookup($form_state['values']['domain_id']);
  module_invoke_all('domainupdate', 'update', $domain, $form_state['values']);
  // The user_submitted flag is needed for Domain User.
  if (empty($form_state['values']['domain_arguments']['user_submitted'])) {
    drupal_set_message(t('Domain record updated.'));
    $form_state['redirect'] = 'admin/build/domain/view';
  }
}

/**
 * Delete a domain record.
 *
 * @param $domain
 * An array containing the record from the {domain} table.
 */
function domain_delete($domain) {
  if ($domain == -1) {
    return t('Invalid page requested.');
  }
  // This action should be performed from the primary domain.
  domain_goto(domain_default());
  return drupal_get_form('domain_delete_form', $domain);

}

/**
 * FormsAPI
 *
 * @param $form_state
 * The current form state, passed by FormsAPI.
 * @param $domain
 * An array containing the record from the {domain} table.
 * @param $arguments
 *  An array of additional hidden key/value pairs to pass to the form.
 *  Used by child modules to control behaviors.
 *  Currently supported arguments are:
 *    'user_submitted' => TRUE
 *    Indicates that a form should not process administrative messages and paths
 *    upon form submit.  Used by the Domain User module.
 */
function domain_delete_form($form_state, $domain, $argumants = array()) {
  $form = array();
  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form['domain'] = array('#value' => t('<p>Are you sure you wish to delete the domain record for <b>%domain</b>?</p>', array('%domain' => $domain['subdomain'])));
  $form['cancel'] = array('#value' => '<p>'. l(t('Cancel action'), 'admin/build/domain') .'<br />');
  $form['submit'] = array('#type' => 'submit', '#value' => t('Delete domain record'), '#suffix' => '<p>');
  return $form;
}

/**
 * FormsAPI for domain_delete_form()
 */
function domain_delete_form_submit($form, &$form_state) {
  // Run the lookup before we delete the row!
  $domain = domain_lookup($form_state['values']['domain_id']);
  db_query("DELETE FROM {domain} WHERE domain_id = %d", $form_state['values']['domain_id']);
  // Let other modules act.
  module_invoke_all('domainupdate', 'delete', $domain, $form_state['values']);
  // The user_submitted flag is needed for Domain User.
  if (empty($form_state['values']['domain_arguments']['user_submitted'])) {
    drupal_set_message(t('Domain record deleted.'));
    $form_state['redirect'] = 'admin/build/domain/view';
  }
}

/**
 * Advanced node-type settings
 */
function domain_advanced() {
  $node_types = node_get_types('names');
  return drupal_get_form('domain_advanced_form', $node_types);
}

/**
 * FormsAPI
 *
 * @param $form_state
 * The current form state, passed by FormsAPI.
 * @param $node_types
 *  A list of active node types taken from node_get_types().
 */
function domain_advanced_form($form_state, $node_types) {
  $form = array();
  // Some editors will not have full node editing permissions.  This allows us
  // to give selected permissions for nodes within the editor's domain.
  if (variable_get('domain_editors', DOMAIN_EDITOR_RULE) == 1) {
    $form['domain_form'] = array(
      '#type' => 'fieldset',
      '#title' => t('Domain node editing'),
      '#collapsible' => TRUE
    );
    $form['domain_form']['intro'] = array('#value' => t('<p>When editors view domain-access restricted nodes, which form elements should be exposed?</p>'));
    $options = array(
      'log' => t('Log messages'),
      'author' => t('Authoring information'),
      'options' => t('Publishing options'),
      'delete' => t('Delete node'),
      'comment_settings' => t('Comment settings'),
      'path' => t('Path aliasing'),
      'menu' => t('Menu settings'),
      'attachments' => t('File attachments')
    );
    ksort($options);
    $form['domain_form']['domain_form_elements'] = array(
      '#type' => 'checkboxes',
      '#default_value' => variable_get('domain_form_elements', array('options', 'delete', 'comment_settings', 'path')),
      '#options' => $options,
      '#description' => t('Some elements may not be editable for all users due to permission restrictions, specifically <em>authoring</a> and menu</em> settings.'),
    );
  }
  $default = variable_get('domain_behavior', DOMAIN_INSTALL_RULE);
  $form['domain_node'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain node types'),
    '#collapsible' => TRUE
  );
  $form['domain_node']['intro'] = array('#value' => t('<p>Check the content types that should be published to all affiliates when new content is created.  <br /><em>NOTE: These settings only apply if the "New content settings" option is set to "Only show on selected sites."</em></p>'));
  foreach ($node_types as $key => $value) {
    $form['domain_node']['domain_node_'. $key] = array(
      '#type' => 'checkbox',
      '#title' => check_plain($value),
      '#default_value' => variable_get('domain_node_'. $key, $default),
    );
  }
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
  $url = domain_get_path($domain);
  $response = drupal_http_request($url);
  if ($response->code != 200) {
    drupal_set_message(t('%server is not responding and may not be configured correctly at the server level.
      Server code !code was returned.',
    array('%server' => $url, '!code' => $response->code)), 'warning');
  }
}

/**
 * Allows for the batch update of certain elements.
 *
 * @param $action
 * The name of the action to perform; corresponds to the keys of the $batch array
 * returned by hook_domainbatch().
 * @return
 * The appropriate form, or a list of actions, or an error.
 */
function domain_batch($action = NULL) {
  // We must have the module configured correctly.
  $domains = domain_domains();
  if (empty($domains)) {
    return t('There are no domains configured for this site.');
  }

  // This action must be performed from the root domain.
  domain_goto(domain_default());

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
    $data = db_query("SELECT domain_id FROM {%s}", $batch[$action]['#table']);
    while ($test = db_result($data)) {
      $allowed[] = $domains[$test];
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
 * An array of batch actions, as defined by hook_domainbatch().
 * @return
 * A themed table of links and descriptions for batch actions.
 */
function domain_batch_list($batch) {
  $header = array(t('Action'), t('Description'));
  $rows = array();
  uasort($batch, 'domain_batch_sort');
  foreach ($batch as $key => $value) {
    $rows[] = array(l($value['#form']['#title'], 'admin/build/domain/batch/'. $key), $value['#meta_description']);
  }
  $output = '<p>'. t('Batch updates allow you to edit values for multiple domains at one time.  These functions are helpful when moving your sites from staging to production, or any time you need to make mass changes quickly.  The following batch update actions may be performed.') .'</p>';
  $output .= '<p><em>'. t('Note that you will only be shown domains on which each action may be performed.  If the module is not yet configured, some actions may be empty.') .'</em></p>';
  return $output . theme('table', $header, $rows);
}

/**
 * Sorting function for domain batch options.
 */
function domain_batch_sort($a, $b) {
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
 * The current form state, passed by FormsAPI.
 * @param $action
 * The name of the action to perform; corresponds to the keys of the $batch array
 * returned by hook_domainbatch().
 * @param $batch
 * The batch data for this $action, as defined by hook_domainbatch().
 * @return
 * A themed table of links and descriptions for batch actions.
 */
function domain_batch_form($form_state, $action, $batch, $domains) {
  $default = array();
  drupal_set_title($batch['#form']['#title']);

  $form = array();
  $form['message'] = array(
    '#type' => 'markup',
    '#value' => theme('domain_batch_title', $batch)
  );
  $form['domain_batch'] = array(
    '#tree' => TRUE,
    '#title' => $batch['#form']['#title'],
    '#description' => $batch['#meta_description']
  );

  foreach ($domains as $domain) {
    // Set the current value according to the stored values.
    $default[$domain['domain_id']] = $domain[$action];
    if ($batch['#domain_action'] == 'domain_conf') {
      // Set the default value to the main settings.
      $default[$domain['domain_id']] = variable_get($action, $batch['#system_default']);
      // Check for domain-specific settings.
      $result = db_result(db_query("SELECT settings FROM {domain_conf} WHERE domain_id = %d", $domain['domain_id']));
      $settings = unserialize($result);
      if (isset($settings[$action])) {
        $default[$domain['domain_id']] = $settings[$action];
      }
    }
    else if ($batch['#domain_action'] == 'custom' && isset($batch['#lookup'])) {
      $default[$domain['domain_id']] = $batch['#system_default'];
      $func = $batch['#lookup'];
      $setting = $func($domain);
      if ($setting != -1) {
        $default[$domain['domain_id']] = $setting;
      }
    }
    // Take the settings from the $batch array.
    $form['domain_batch'][$domain['domain_id']] = $batch['#form'];
    // Add the domain-specific elements.
    $form['domain_batch'][$domain['domain_id']]['#sitename'] = $domain['sitename'];
    $form['domain_batch'][$domain['domain_id']]['#default_value'] = $default[$domain['domain_id']];
  }
  $form['handler'] = array('#type' => 'value', '#value' => $batch['#domain_action']);
  $form['variable'] = array('#type' => 'value', '#value' => $batch['#variable']);
  $form['table'] = array('#type' => 'value', '#value' => $batch['#table']);
  $form['submit_handler'] = array('#type' => 'value', '#value' => $batch['#submit']);
  $form['validate_handler'] = array('#type' => 'value', '#value' => $batch['#validate']);
  $form['data_type'] = array('#type' => 'value', '#value' => $batch['#data_type']);
  $form['batch_item'] = array('#type' => 'value', '#value' => $action);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Update domain settings'));
  return $form;
}

/**
 * FormsAPI
 */
function theme_domain_batch_form($form) {
  $output = '';
  $output = drupal_render($form['message']);
  $header = array(t('Id'), t('Domain'), t('Setting'));
  $rows = array();
  foreach (element_children($form['domain_batch']) as $key) {
    $temp = $form['domain_batch'][$key]['#title'];
    unset($form['domain_batch'][$key]['#title']);
    $row = array($key, $form['domain_batch'][$key]['#sitename'], drupal_render($form['domain_batch'][$key]));
    $rows[] = $row;
    $form['domain_batch'][$key]['#title'] = $temp;
  }
  $output .= drupal_render($form['domain_batch']);
  $output .= theme('table', $header, $rows);
  $output .= drupal_render($form);
  return $output;
}

/**
 * Theme function for displaying batch editing.
 *
 * @param $batch
 * the batch function to be performed.
 */
function theme_domain_batch_title($batch) {
  $output = '';
  $output = '<p>'. $batch['#meta_description'] .'</p>';
  if (!empty($batch['#required'])) {
    $output .= '<p><em>'. t('All values are required.') .'</em></p>';
  }
  return $output;
}

/**
 * FormsAPI for validating batch form actions.
 */
function domain_batch_form_validate($form, &$form_state) {
  // Define the validation function and call it.
  $validate = $form_state['values']['validate_handler'];
  if (function_exists($validate)) {
    $validate($form_state['values']);
  }
}

/**
 * FormsAPI for saving batch form actions.
 */
function domain_batch_form_submit($form, &$form_state) {
  $item = $form_state['values']['batch_item'];
  $type = $form_state['values']['data_type'];
  $variable = $form_state['values']['variable'];
  $table = $form_state['values']['table'];
  switch ($form_state['values']['handler']) {
    case 'domain':
      foreach ($form_state['values']['domain_batch'] as $key => $value) {
        if ($key > 0) {
          // Note that $type in the query below is a sanitzed value passed from domain_batch_data_type().
          db_query("UPDATE {domain} SET %s = ". domain_batch_data_type($type) ." WHERE domain_id = %d", $item, $value, $key);
        }
        else if (!empty($variable)) {
          variable_set($variable, $value);
        }
      }
      break;
    case 'domain_conf':
      foreach ($form_state['values']['domain_batch'] as $key => $value) {
        $settings = array();
        if ($key > 0 || empty($variable)) {
          $data = db_fetch_array(db_query("SELECT settings FROM {domain_conf} WHERE domain_id = %d", $key));
          if (isset($data['settings'])) {
            $settings = unserialize($data['settings']);
            $settings[$item] = $value;
            db_query("UPDATE {domain_conf} SET settings = %b WHERE domain_id = %d", serialize($settings), $key);
          }
          else {
            $settings[$item] = $value;
            db_query("INSERT INTO {domain_conf} (domain_id, settings) VALUES (%d, %b)", $key, serialize($settings));
          }
        }
        else if (!empty($variable)) {
          variable_set($variable, $value);
        }
      }
      break;
    case 'domain_delete':
      foreach ($form_state['values']['domain_batch'] as $key => $value) {
        if ($value == 1) {
          if (is_array($table)) {
            foreach ($table as $current) {
              db_query("DELETE FROM {%s} WHERE domain_id = %d", $current, $key);
            }
          }
          else {
            db_query("DELETE FROM {%s} WHERE domain_id = %d", $table, $key);
          }
        }
      }
      break;
    case 'custom':
      if (function_exists($form_state['values']['submit_handler'])) {
        $func = $form_state['values']['submit_handler'];
        $func($form_state['values']);
      }
      break;
  }
  drupal_set_message(t('Settings have been updated successfully.'));
}

/**
 * Converts a data type indicator into a sql-safe string.
 *
 * @param $type
 * The data type defined in hook_domainbatch().
 * @return
 * A sql-safe string ('%s', %d, %f, %b) for use with db_query().
 */
function domain_batch_data_type($type) {
  $return = "'%s'";
  switch ($type) {
    case 'string':
      break;
    case 'integer':
      $return = "%d";
      break;
    case 'float':
      $return = "%f";
      break;
    case 'binary':
      $return = "%b";
      break;
  }
  return $return;
}
