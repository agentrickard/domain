<?php

// $Id$

/**
 * @file
 * Administration functions for the domain module.  
 *
 * These functions only need to be accessed from admin/build/domain, so we put them in a separate include file.
 */
 
/**
 * The main administration page, a list of active domains.
 *
 * @return
 *  A sortable table of active domain records, or an error message.
 *
 * @ingroup domain
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
    $extra = module_invoke_all('domainload', $domain);
    $domain = array_merge($domain, $extra);  
    $link = l($domain['subdomain'], domain_get_uri($domain), array(), NULL, NULL, TRUE);
    if ($domain['domain_id'] == 0) {
      $actions = l(t('Default settings'), 'admin/build/domain/settings');
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
    $row = array($domain['domain_id'], ($domain['subdomain'] == $_domain['subdomain']) ? '<b>'. $link .'</b>' : $link, $domain['sitename'], $valid, $domain['scheme']);
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
 *
 * @ingroup domain 
 */
function domain_configure() {
  if (!$_POST) {
    // Is the module installed correctly?
    module_invoke_all('domaininstall');
  }  
  // Return the configuration form.
  return drupal_get_form('domain_configure_form');
}

/**
 * FormsAPI for configuring the domain module.
 *
 * @ingroup domain
 */
function domain_configure_form($user_submitted = FALSE) {
  $form = array();
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default domain settings'),
    '#collapsible' => TRUE
  );  
  $form['#redirect'] = 'admin/build/domain';
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
  $form['#submit']['domain_admin_editor_form'] = array();  
  $form['domain_editors_status'] = array('#type' => 'value', '#value' => variable_get('domain_editors', 0));
  // Adding this submit behavior disabled the default, so we add that explictly.
  $form['#submit']['system_settings_form_submit'] = array();
  
  // Check to see if the node_access patch is enabled.  If it is, then
  // we need to know if more than one node_access module is running.
  $disabled = FALSE;
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
    '#description' => t('Options for content searching.  This feature requires the hook_url_alter() patch.')
  );  
  
  $form['domain_advanced']['domain_seo'] = array(
    '#type' => 'radios',
    '#title' => t('Search engine optimization'),
    '#disabled' => $disabled,
    '#default_value' => variable_get('domain_seo', 0),
    '#options' => array(0 => t('Do not rewrite URLs'), 1 => t('Rewrite all URLs to point to a single source')),
    '#description' => t('If rewrite is turned on, all node links will point to a single instance of the node.  This
      option reduces the chance that search engines will recognize duplicate content.  
      This feature requires the hook_url_alter() patch.')
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
  // The hook_url_alter() patch must be present for these settings to be applied.
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
 *
 * @ingroup domain
 */
function domain_admin_editor_form($form_id, $form_values) {
  $clear = FALSE;
  if ($form_values['domain_editors_status'] == 0 && $form_values['domain_editors'] == 1) {
    // Apply rules
    $result = db_query("SELECT nid, gid FROM {node_access} WHERE realm = 'domain_id'");
    while ($data = db_fetch_array($result)) {
      db_query("INSERT INTO {node_access} VALUES (%d, %d, 'domain_editor', 0, 1, 1)", $data['nid'], $data['gid']);
      db_query("INSERT INTO {domain_access} VALUES (%d, %d, 'domain_editor')", $data['nid'], $data['gid']);      
    }
    $clear = TRUE;
  }
  else if ($form_values['domain_editors_status'] == 1 && $form_values['domain_editors'] == 0) {
    // Remove rules
    db_query("DELETE FROM {node_access} WHERE realm = 'domain_editor'");
    db_query("DELETE FROM {domain_access} WHERE realm = 'domain_editor'");    
    $clear = TRUE;
  }
  // We have reset the node_access table and need to clear the cache.
  if ($clear) {
    cache_clear_all();
  }
}

/**
 * Create a new domain record
 *
 * @ingroup domain
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
 *
 * @ingroup domain
 */
function domain_create_form($arguments = array()) { 
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
    '#description' => t('The allowed subdomain, using the full path.example.com format.  Can only contain lower-case alphanumeric characters.  Leave off the http:// and the trailing slash.')
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
 *
 * @ingroup domain
 */
function domain_create_form_validate($form_id, $form_values) {
  // TODO: Make this a proper regex?
  $subdomain = strtolower(urlencode($form_values['subdomain']));
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE subdomain = '%s'", $subdomain));
  if ($check) {
    form_set_error('subdomain', t('The subdomain value must be unique.'));
  }
  $check = NULL;
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE sitename = '%s'", $form_values['sitename']));
  if ($check) {
    form_set_error('sitename', t('The site name value must be unique.'));
  }  
}

/**
 * FormsAPI for domain_create_form()
 *
 * @ingroup domain 
 */
function domain_create_form_submit($form_id, $form_values) {
  db_query("INSERT INTO {domain} (subdomain, sitename, scheme, valid) VALUES ('%s', '%s', '%s', %d)", $form_values['subdomain'], $form_values['sitename'], $form_values['scheme'], $form_values['valid']);
  // Let other modules act.
  $domain = domain_lookup(NULL, $form_values['subdomain']);
  module_invoke_all('domainupdate', 'create', $domain, $form_values);
  // The user_submitted flag is needed for Domain User.
  if (!$form_values['domain_arguments']['user_submitted']) { 
    drupal_set_message(t('Domain record created.'));
    drupal_goto('admin/build/domain/view');
  }  
}

/**
 * Edit an existing domain record
 * 
 * @param $domain_id
 *  The unique id for this domain, taken from {domain}.
 *
 * @ingroup domain
 */
function domain_edit($domain_id) {
  $domain = domain_lookup($domain_id);
  // We must do this from the default domain, in case the URL does not resolve.
  $default = domain_default();
  domain_goto($default);  
  return drupal_get_form('domain_edit_form', $domain);
}

/**
 * FormsAPI for editing a domain record
 *
 * @param $domain
 * An array containing the record from the {domain} table
 *
 * @ingroup domain 
 */
function domain_edit_form($domain) { 
  domain_check_response($domain);
  $form = array();
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
    '#description' => t('The allowed subdomain, using the full path.example.com format.  Can only contain lower-case alphanumeric characters.  Leave off the http:// and the trailing slash.')
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
 *
 * @ingroup domain 
 */
function domain_edit_form_validate($form_id, $form_values) {
  // TODO: Make this a proper regex
  $subdomain = strtolower(urlencode($form_values['subdomain']));
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE subdomain = '%s' AND domain_id != %d", $subdomain, $form_values['domain_id']));
  if ($check) {
    form_set_error('subdomain', t('The subdomain value must be unique.'));
  }
  $check2 = db_result(db_query("SELECT COUNT(domain_id) FROM {domain} WHERE sitename = '%s' AND domain_id != %d", $form_values['sitename'], $form_values['domain_id']));
  if ($check2) {
    form_set_error('sitename', t('The site name value must be unique.'));
  }  
}

/**
 * FormsAPI for domain_edit_form()
 *
 * @ingroup domain 
 */
function domain_edit_form_submit($form_id, $form_values) {
  db_query("UPDATE {domain} SET subdomain = '%s', sitename = '%s', scheme = '%s', valid = %d WHERE domain_id = %d", $form_values['subdomain'], $form_values['sitename'], $form_values['scheme'], $form_values['valid'], $form_values['domain_id']);
  // Let other modules act.
  $domain = domain_lookup($form_values['domain_id']);
  module_invoke_all('domainupdate', 'update', $domain, $form_values);
  drupal_set_message(t('Domain record updated.'));
  drupal_goto('admin/build/domain/view');  
}

/**
 * Delete a domain record.
 * 
 * @param $domain_id
 *  The unique id for this domain, taken from {domain}. 
 *
 * @ingroup domain 
 */
function domain_delete($domain_id) {
  $domain = domain_lookup($domain_id);
  return drupal_get_form('domain_delete_form', $domain);
}

/**
 * FormsAPI
 *
 * @param $domain
 * An array containing the record from the {domain} table
 *
 * @ingroup domain 
 */
function domain_delete_form($domain) { 
  $form = array(); 
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);  
  $form['domain'] = array('#value' => t('<p>Are you sure you wish to delete the domain record for <b>%domain</b>?</p>', array('%domain' => $domain['subdomain'])));
  $form['cancel'] = array('#value' => '<p>'. l(t('Cancel action'), 'admin/build/domain') .'<br />');
  $form['submit'] = array('#type' => 'submit', '#value' => t('Delete domain record'), '#suffix' => '<p>');
  return $form;
}

/**
 * FormsAPI for domain_delete_form()
 *
 * @ingroup domain 
 */
function domain_delete_form_submit($form_id, $form_values) {
  // Run the lookup before we delete the row!
  $domain = domain_lookup($form_values['domain_id']);
  db_query("DELETE FROM {domain} WHERE domain_id = %d", $form_values['domain_id']);
  // Let other modules act.
  module_invoke_all('domainupdate', 'delete', $domain, $form_values);
  drupal_set_message(t('Domain record deleted.'));
  // Some tortured logic to return to the root domain and the domain list.
  $default = domain_default();
  $uri = request_uri();
  $_path = explode('/', $uri);
  $_slice = array_slice($_path, 0, -2);
  $path = implode('/', $_slice) . '/view';
  $goto = $default['scheme'] .'://'. $default['subdomain'] . $path;
  drupal_goto($goto);  
}

/**
 * Advanced node-type settings
 *
 * @ingroup domain 
 */
function domain_advanced() {
  $node_types = node_get_types('names');
  return drupal_get_form('domain_advanced_form', $node_types);  
}

/**
 * FormsAPI
 *
 * @param $node_types
 *  A list of active node types taken from node_get_types().
 *
 * @ingroup domain 
 */
function domain_advanced_form($node_types) {
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
    );
  }
  $default = variable_get('domain_behavior', DOMAIN_INSTALL_RULE);
  $form['domain_node'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain node types'),
    '#collapsible' => TRUE
  );  
  $form['domain_node']['intro'] = array('#value' => t('<p>Check the content types that should be published to all affiliates when new content is created.</p>'));
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
 *
 * @ingroup domain
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
