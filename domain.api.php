<?php

/**
 * @defgroup domain_hooks Domain hook functions
 *
 * Core hooks for the Domain module suite.
 */

/**
 * @file
 * API documentation file.
 *
 * @ingroup domain_hooks
 */

/**
 * Notifies other modules that we are loading a domain record from the database.
 *
 * Modules may overwrite or add to the $domain array for each domain.
 *
 * When loading lists of domains or generating domain information, either use
 * the proper functions -- domain_default(), domain_lookup(), and
 * domain_domains() -- or invoke this hook.
 *
 * Invoked by domain_lookup() and domain_default().
 *
 * @param &$domain
 *   The current $domain array.
 *
 * @return
 *   No return value.  The $domain array is modified by reference..
 *
 * @ingroup domain_hooks
 */
function hook_domain_load(&$domain) {
  // Add a variable to the $domain array.
  $domain['myvar'] = 'mydomainvar';
  // Remove the site_grant flag, removing access to 'all affiliates.'
  $domain['site_grant'] = FALSE;
}

/**
 * Notify other modules that we have created a new domain.
 *
 * @param $domain
 *  The domain record taken from {domain}, as an array.
 * @param $form_values
 *   The form values processed by the form.  Note that these are not editable
 *   since module_invoke_all() cannot pass by reference. They are passed in
 *   case some module needs to check the original form input.
 *
 * @ingroup domain_hooks
 */
function hook_domain_insert($domain, $form_values = array()) {
  db_insert('mytable')
    ->fields(array(
      'domain_id' => $domain['domain_id'],
      'myvar' => 1,
    ))
    ->execute();
}

/**
 * Notify other modules that we have updated a domain.
 *
 * @param $domain
 *  The domain record taken from {domain}, as an array.
 * @param $form_values
 *   The form values processed by the form.  Note that these are not editable
 *   since module_invoke_all() cannot pass by reference. They are passed in
 *   case some module needs to check the original form input.
 *
 * @ingroup domain_hooks
 */
function hook_domain_update($domain, $form_values = array()) {
  db_update('mytable')
    ->fields(array(
      'status' => 1,
    ))
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
}

/**
 * Notify other modules that we have deleted a domain.
 *
 * @param $domain
 *  The domain record taken from {domain}, as an array.
 * @param $form_values
 *   The form values processed by the form.  Note that these are not editable
 *   since module_invoke_all() cannot pass by reference. They are passed in
 *   case some module needs to check the original form input.
 *
 * @ingroup domain_hooks
 */
function hook_domain_delete($domain, $form_values = array()) {
  db_delete('mytable')
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
}

/**
 * Enables modules to add additional parameters to the $domain array
 * for use by the Domain Navigation module.
 *
 * Used in cases where custom themes may require extra parameters.
 * This hook is called by domain_nav_render().
 *
 * @param $domain
 *   The information for the current domain record, taken from {domain}.
 *
 * Default parameters should not be changed; these are:
 *
 *   - domain_id -- the unique identifier of this domain
 *   - subdomain -- the host path of the url for this domain
 *   - sitename -- the human-readable name of this domain
 *   - path -- the link path (a Drupal-formatted path)
 *   - active -- a boolean flag indicating the currently active domain
 *
 * @ingroup domain_hooks
 */
function hook_domain_nav($domain) {
  $extra = array();
  $extra['test'] = 'test';
  return $extra;
}

/**
 * Enables Domain Access modules to fire cron hooks across all
 * active domains.
 *
 * Each module implementing this hook will have the function run
 * once per active domain record.  The global $_domain variable
 * will be set to the current $domain passed as an argument.
 *
 * This function is especially useful if you need to run node queries
 * that obey node access rules.
 *
 * Note that Domain Prefix and Domain Conf are activated by this hook.
 * That means each domain will have its tables and variables loaded before
 * your function fires.
 *
 * @param $domain
 *   The information for the current domain record, taken from {domain}.
 *
 * @ingroup domain_hooks
 */
function hook_domain_cron($domain) {
  // Run a node query.
  $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->condition('status', 1)
    ->orderBy('created', 'DESC')
    ->extend('PagerDefault')
    ->limit(1);
  // Note that we don't tag this query with 'node_access' because it is an
  // administrative query and we want to return all nodes for a specific domain.
  $select->join('domain_access', 'da', 'n.nid = da.nid AND da.domain_id = :domain_id',
    array(':domain_id' => $domain['domain_id']));
  $nid = $select->execute()->fetchCol();
  if ($node = node_load($nid)) {
    // Set a variable for each domain containing the last node updated.
    variable_set('domain_' . $domain['domain_id'] . '_lastnode', $node->nid);
  }
}

/**
 * Some Domain modules require that settings.php be edited to add
 * additional files during the bootstrap process.
 *
 * This hook allows those modules to check to see if they have been installed
 * correctly.  Usually the module is enabled, but the required function is not.
 *
 * @see domain_conf_domain_install()
 *
 * @ingroup domain_hooks
 */
function hook_domain_install() {
  // If MyModule is being used, check to see that it is installed correctly.
  if (module_exists('mymodule') && !function_exists('_mymodule_load')) {
    drupal_set_message(t('MyModule is not installed correctly.  Please edit your settings.php file as described in <a href="!url">INSTALL.txt</a>', array('!url' => drupal_get_path('module', 'mymodule') . '/INSTALL.txt')));
  }
}

/**
 * Allows other modules to add elements to the default Domain settings page.
 *
 * @param &$form
 *   The $form array generated for the Domain settings page.  This must
 *   be passed by reference. Normally, you should include your form elements
 *   inside a new fieldset.
 *
 * @return
 *   No return value.  The $form is modified by reference, as needed.
 */
function hook_domain_form(&$form) {
  // Add the form element to the main screen.
  $form['domain_mymodule'] = array(
    '#type' => 'fieldset',
    '#title' => t('Mymodule settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $options = drupal_map_assoc(array(-100, -25, -10, -5, -1, 0, 1, 5, 10, 25, 100));
  $form['domain_mymodule']['domain_mymodule'] = array(
    '#type' => 'select',
    '#title' => t('Mymodule settings variable'),
    '#options' => $options,
    '#default_value' => variable_get('domain_mymodule', 0),
    '#description' => t('You description goes here.')
  );
}

/**
 *  Allows a warning message to be printed when entering specific forms that
 *  may have values that vary on each domain.
 *
 * @return
 *   An associative array where the keys form_id values representing forms
 *   that require warnings. The value should return a link for where the
 *   form may be set for the current domain. If no link exists, you should
 *   pass NULL as the value.
 *
 *   These values are subject to token replacement, using the syntax
 *   %value, where %NAME may be any element of the $_domain array.
 *
 * @ingroup domain_hooks
 */
function hook_domain_warning() {
  // These are the forms for variables set by Domain Conf.
  $forms = array(
    'system_admin_theme_settings',
    'system_date_time_settings',
    'system_site_information_settings',
    'system_site_maintenance_settings'
  );
  $return = array();
  foreach ($forms as $key) {
    $return[$key] = 'admin/build/domain/path/%domain_id';
  }
  return $return;
}

/**
 * Allows modules to specify the target link for a node.
 *
 * @param &$source
 *   The domain array from domain_get_node_match(), passed by reference.
 * @param $nid
 *   The node id.
 *
 * @return
 *   No return value; modify $source by reference.
 */
function hook_domain_source_alter(&$source, $nid) {
  // Taken from the Domain Source module
  $source = domain_source_lookup($nid);
}

/**
 * Allows modules to specify the target link for a Drupal path.
 *
 * Note: This hook is not meant to be used for node paths, which
 * are handled by hook_domain_source_alter(). This hook is split
 * from hook_domain_source_alter() for better performance.
 *
 * Currently, no modules in the package implement this hook.
 *
 * @param &$source
 *   The domain array from domain_get_node_match(), passed by reference.
 * @param $nid
 *   The identifier of the object being rewritten. For nodes, this is the node
 *   id. In other instances, we may pass a $path string or other variable.
 *
 * @return
 *   No return value; modify $source by reference.
 */
function hook_domain_source_path_alter(&$source, $path) {
  // Always make admin links go to the primary domain.
  $base = arg(0, $path);
  if ($base == 'admin') {
    $source = domain_default();
  }
}

/**
 * Allows modules to add additional form elements for saving as domain-specific
 * settings. Note that this is a "convenience function" to use in place of
 * hook_form_alter(), so you may use either. Versions prior to 7.x.3 are not
 * complete replacements for hook_form_alter().
 *
 * When naming your form arrays, remember that the final key is the name of
 * the variable that you wish to alter.  The example below changes the default
 * user picture depending on the active domain.
 *
 * Preferred use is to wrap your form elements in a named fieldset, for easier
 * viewing.
 *
 *  This hook is implemented by the Domain Conf module.
 *
 * You may wish to pair this hook with hook_domain_batch() to allow the mass
 * update of your settings.
 *
 * If you wish to store settings that are not related to another module, you
 * must pass the following parameter:
 *
 * $form['myform']['#domain_setting'] = TRUE;
 *
 * Doing so will tell Domain Access that no default settings page exists, and
 * that values must be stored for the primary domain.  This feature is useful
 * for creating special data that needs to be associated with a domain record
 * but does not need a separate table.
 *
 * Using the variable override of hook_domain_conf() is an alternative to
 * creating a module and database table for use with hook_domain_load().
 *
 * NOTE: The responding module is required to check that the user has access to
 * this form setting. Failure to check access on the form elements may introduce
 * a security risk.
 *
 * @return
 *   A $form array element as defined by the FormsAPI.
 *
 *  @ingroup domain_hooks
 */
function hook_domain_conf() {
  $form['pictures'] = array(
    '#type' => 'fieldset',
    '#title' => t('User picture'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['pictures']['user_picture_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default picture'),
    '#default_value' => variable_get('user_picture_default', ''),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('URL of picture to display for users with no custom picture selected. Leave blank for none.')
  );
  return $form;
}

/**
 * Allows modules to expose batch editing functions.
 *
 * This hook makes it easier for site administrators to perform
 * bulk updates.  It is most useful for handling settings changes
 * caused by moving from a staging to a production server.
 *
 * The function works by defining a $batch array that serves as a combination
 * of menu hook and form element.  The $batch array contains all the information
 * needed to create an administrative page and form that will process your
 * settings.
 *
 * For a basic example, see domain_domain_batch().
 *
 * For a more complex example, with custom processing, see
 * domain_theme_domain_batch().
 *
 * The $batch array is formatted according to the following rules:
 *
 * - '#form' [required] An array that defines the form element for this item.
 *  It accepts any values defined by the FormsAPI.  Do not, however, pass the
 *  #default_value element here.  That value will be computed dynamically for
 *  each domain when the hook is processed.
 *
 *  - '#system_default' [required] Used to fill the #default_value parameter for
 *  domains that do not have custom settings. Typically, this will be the result
 *  of a variable_get().  For domain_delete operations, this value should be set
 *  to zero (0).
 *
 * - '#meta_description' [required] Used to describe your action to end users.
 *
 *  - '#override_default' [optional] A boolean value used to tell whether to use
 *  variable_get() to retrieve the current value. Use this when complex
 *  variables do not allow a normal usage.
 * - '#domain_action' [required] Indicates what submit action will be invoked
 *  for this setting.  Allowed values are:
 *   --- 'domain' == writes the value to the {domain} table.  Normally,
 *   contributed modules will not use this option.
 *   --- 'domain_conf' == writes the value to the {domain_conf} table.  Use in
 *   connection with hook_domain_conf().
 *   --- 'domain_delete' == used to delete rows from specific tables.  If this
 *   is used, the #table value must be present.
 *   --- 'custom' == used if you need your own submit handler. Must be paired
 *   with a #submit parameter.
 *
 * - '#permission' [optional] A string identifying the permission required to
 * access this setting. If not provided, defaults to 'administer domains'.
 *
 * - '#permission' [optional] A string identifying the permission required to
 * access this setting. If not provided, defaults to 'administer domains'. Your
 * module should ensure that the proper permission is set here.
 *
 * - '#submit' [optional] Used with the 'custom' #domain_action to define a
 * custom submit handler for the form.  This value should be a valid function
 * name.  It will be passed the $form_values array for processing.
 *
 * - '#validate' [optional] Used to define a validate handler for the form.
 * This value
 * should be a valid function name.  It will be passed the $form_values array
 * for processing.
 *
 * - '#lookup' [optional] Used with the 'custom' #domain_action to perform a
 * default value lookup against a custom function. This value should be a valid
 * function name.  Your function must accept the $domain array as a parameter.
 *
 * - '#table' [optional] Used with the 'domain_delete' #domain_action to specify
 * which table a row should be deleted from. This value may be a string or an
 * array, if you need to perform multiple deletes.  Deletes are performed
 * against the domain_id of the selected domains.
 *
 * - '#variable' [optional] Used to perform changes for the default domain,
 * which is stored in the {variables} table. If this value is not set, the root
 * domain will not be exposed for batch editing.
 *
 * - '#data_type' [optional] Used to tell the system how to build your data
 * entry query.  Defaults to 'string'; possible values are:
 *   --- 'string' == the query will use '%s' to insert the data.
 *   --- 'integer' == the query will use %d to insert the data.
 *   --- 'float' == the query will use %f to insert the data.
 *   --- 'binary' == the query will use %b to insert the data.
 * For more information, see db_query() in the Drupal API documentation.
 *
 * - '#weight' [optional] Used to weight the item in the menu system.  Should
 * normally be set to zero.  Negative values are reserved for use by the core
 * Domain Access module.  The following values are in use:
 * --- (-10) items used by Domain Access core.
 * --- (-8) items used by Domain Configuration.
 * --- (-6) items used by Domain Theme.
 * --- (-2) items reserved for batch delete actions.
 *
 * - '#group' [optional] Used to place elements into fieldsets for the main
 * domain configuration page. If not set, any new element will be added to the
 * 'Site configuration' fieldset.
 *
 * - '#collapsed' [optional] Indicates that the form fieldset should appear
 * collapsed on the configuration page.
 *
 * - '#update_all' [optional] Allows the batch settings form to use one input
 * field to reset all values. This should be set to TRUE in most cases. If your
 * value must be unique per domain, set this to FALSE or leave empty.
 *
 * - '#module' [optional] Used to group like elements together on the batch
 * action list.
 *
 * @ingroup domain_hooks
 */
function hook_domain_batch() {
  // A simple function to rename my setting in Domain Configuration.
  $batch = array();
  $batch['mysetting'] = array(
    '#form' => array(
      '#title' => t('My Settings'),
      '#type' => 'textfield',
      '#size' => 40,
      '#maxlength' => 80,
      '#description' => t('A description for the form'),
      '#required' => TRUE,
    ),
    '#permission' => 'administer domains',
    '#domain_action' => 'domain_conf',
    '#meta_description' => t('Edit my setting value.'),
    '#variable' => 'domain_mysetting',
    '#validate' => 'domain_mysetting_validate',
    '#data_type' => 'string',
    '#weight' => 0,
    '#group' => t('My settings'),
    '#collapsed' => FALSE,
    '#update_all' => TRUE,
    '#module' => t('Domain Access'),
  );
  return $batch;
}

/**
 * Allow modules to modify the batch editing functions.
 *
 * @see drupal_alter()
 *
 * @param &$batch
 *   An array of batch editing functions, passed by reference.
 * @return
 *   No return value. Modify $batch by reference.
 */
function hook_domain_batch_alter(&$batch) {
  // Rename 'Put site into maintenance mode'.
  $batch['maintenance_mode']['#form']['#title'] = t('Take site offline');
}

/**
 * Return an array of forms for which we cannot run hook_form_alter().
 *
 * @return
 *   An array of form ids that should not run through domain_form_alter.
 */
function hook_domain_ignore() {
  // User login should always be from the current domain.
  return array('user_login');
}

/**
 * The Domain Bootstrap Process.
 *
 * There are some variables that Domain Access and its modules
 * need to set before Drupal finishes loading. In effect, we have to add
 * stages to the Drupal bootstrap process.
 *
 * These processes are initiated after settings.php is loaded, during
 * DRUPAL_BOOTSTRAP_CONFIGURATION. We skip ahead and
 * load DRUPAL_BOOTSTRAP_DATABASE to access db_query() and
 * similar functions.  However, the majority of Drupal functions are
 * not yet available.
 *
 * The following modules will load during the bootstrap process, if enabled:
 *  -- domain
 *  -- domain_alias
 *  -- domain_conf
 *
 * If you create a custom module, it must be registered with the Domain
 * Bootstrap Process. To register, you must:
 *
 * 1) Implement either or both of the following hooks:
 *  -- hook_domain_bootstrap_loookup().
 *  -- hook_domain_bootstrap_full().
 * 2) Run domain_bootstrap_register() in mymodule_enable().
 * 3) Run domain_bootstrap_unregister('mymodule') in mymodule_disable().
 *
 */
function hook_domain_bootstrap() {
  // Documentation function.
}

/**
 * Allow modules to modify the lookup of the domain record.
 *
 * During the bootstrap phase, Domain Access will try to assign the active
 * domain based on the inbound HTTP_HOST request. Modules using this
 * hook may alter this behavior in order to account for special conditions.
 *
 * This alteration happens before any other module functions are called, so it
 * allows you to modify which domain is loaded at the start of a page request.
 *
 * For example, Domain Alias can change the domain_id matched to the current
 * domain name before related information is retrieved during domain_init().
 *
 * Note: Because this function is usually called VERY early, many Drupal
 * functions or modules won't be loaded yet.
 *
 * Warning: do _not_ call domain_lookup() or domain_load() from within this
 * function. Doing so may cause critical errors.
 *
 * @param $domain
 *   An array containing current domain (host) name (used during bootstrap) and
 *   the results of lookup against {domain} table.
 * @return
 *   An array containing at least a valid domain_id.
 */
function hook_domain_bootstrap_lookup($domain) {
  // Match en.example.org to default domain (id:0)
  if ($domain['subdomain'] == 'en.example.org') {
    $domain['domain_id'] = 0;
  }
  return $domain;
}

/**
 * Allows modules to execute code before Drupal's hook_boot().
 *
 * This hook can be used to modify drupal's variables system or prefix database
 * tables, as used in the module domain_conf.
 *
 * Note: Because this function is usually called VERY early, many Drupal
 * functions or modules won't be loaded yet.
 *
 * Warning: do _not_ call domain_lookup() or domain_load() from within this
 * function. Doing so may cause critical errors.
 *
 * @param $domain
 *   An array containing current domain and domain_id and any other values
 *   added during domain bootstrap phase DOMAIN_BOOTSTRAP_DOMAINNAME_RESOLVE.
 *
 * @return
 *   No return value. However, if you wish to set an error message on failure,
 *   you should load and modify the $_domain global and add an 'error' element
 *   to the array. This element should only include the name of your module.
 *   We do this because drupal_set_message() and t() are not yet loaded.
 *
 *   Normally, you do not need to validate errors, since this function will not
 *   be called unless $domain is set properly.
 */
function hook_domain_bootstrap_full($domain) {
  global $conf;
  // The language variable should not be set yet.
  // Check for errors.
  if (isset($conf['language'])) {
    global $_domain;
    $_domain['error'] = 'mymodule';
    return;
  }
  // Our test module sets the default language to Spanish.
  $conf['language'] = 'es';
}

/**
 * Allows modules to alter path when rewriting URLs.
 *
 * This hook will fire for all paths and may be resource-intensive.
 * Look at Domain Prefix for best practices implementation. In Domain
 * Prefix, we only include this function if we know it is necessary.
 *
 * @see hook_url_outbound_alter()
 *
 * @param $domain_id
 *   The domain_id taken from {domain}.
 * @param $path
 *   The internal drupal path to the node.
 * @param $options
 *   The path options.
 * @param $original_path
 *   The raw path request from the URL.
 *
 * @ingroup domain_hooks
 */
function hook_domain_path($domain_id, &$path, &$options, $original_path) {
  // Give a normal path alias
  $path = drupal_get_path_alias($path);
  // In D7, path alias lookups are done after url_alter, so if the
  // alias is set, the option must be flagged.
  $options['alias'] = TRUE;
}

/**
 * Allows module to reset domain-specific variables.
 *
 * This function is not a hook, it is a helper function that is implemented by
 * the Domain Configuration module.
 *
 * Use this function if you need to reset a domain-specific variable
 * from your own code. It is especially useful in conjunction with
 * hook_domain_update().
 *
 * @link http://drupal.org/node/367963
 *
 * @param $domain_id
 *   The unique domain ID that is being edited.
 * @param $variable
 *   The name of the variable you wish to set.
 * @param $value
 *   The value of the variable to set. You may leave this
 *   value blank in order to unset the custom variable.
 */
function mymodule_form_submit($form_state) {
  // When we save these changes, replicate them across all domains.
  if (!module_exists('domain_conf')) {
    return;
  }
  $domains = domain_domains();
  foreach ($domains as $domain) {
    $value = $form_state['values']['my_variable'];
    domain_conf_variable_set($domain['domain_id'], 'my_variable', $value);
  }
}

/**
 * Allow modules to alter access to Domain Navigation items.
 *
 * This drupal_alter hook exposes the $options array before
 * Domain Nav passes its links to the theme layer. You can use it
 * to introduce additional access controls on those links.
 *
 * Note that "inactive" domains are already filtered before this
 * hook is called, so you would have to explicitly add them again.
 *
 * @see drupal_alter()
 * @see theme_domain_nav_default()
 *
 * @param &$options
 *   The link options, passed by reference, to the theme.
 * @return
 *   No return value. Modify $options by reference.
 */
function hook_domain_nav_options_alter(&$options) {
  global $user;
  domain_user_set($user);

  // Remove domains that the user is not a member of.
  if (empty($user->domain_user)) {
    $options = array();
  }
  else {
    foreach ($options as $key => $value) {
      if (!in_array($key, $user->domain_user)) {
        unset($options[$key]);
      }
    }
  }
}

/**
 * Allows modules to remove form_ids from the list set
 * by hook_domain_warning().
 *
 * Required by Domain Settings, whose code is shown below.
 *
 * @param &$forms
 *   An array of form_ids, passed by reference.
 */
function hook_domain_warning_alter(&$forms) {
  // Forms which Domain Settings handles and are set as warnings.
  $core_forms = array(
    'system_admin_theme_settings',
    'system_site_information_settings',
    'system_site_maintenance_settings',
    'menu_configure',
  );
  foreach ($core_forms as $form_id) {
    if (isset($forms[$form_id])) {
      unset($forms[$form_id]);
    }
  }
}

/**
 * Notify other modules that Domain Settings has saved a variable set.
 *
 * @param $domain_id
 *   The domain the variable is being saved for. This is not always
 *   the current domain.
 * @param $values
 *   The form values being submitted, an array in the format $name => $value.
 *
 *  @return
 *   No return required.
 */
function hook_domain_settings($domain_id, $values) {
  // Sync domain 2 with the primary domain in all cases.
  if ($domain_id == 2) {
    foreach($values as $name => $value) {
      variable_set($name, $value);
    }
  }
}

/**
 * Alter the validation step of a domain record.
 *
 * This hook allows modules to change or extend how domain validation
 * happens. Most useful for international domains or other special cases
 * where a site wants to restrict domain creation is some manner.
 *
 * NOTE: This does not apply to Domain Alias records.
 *
 * @param &$error_list
 *   The list of current validation errors. Modify this value by reference.
 *   If you return an empty array or NULL, the domain is considered valid.
 * @param $subdomain
 *   The HTTP_HOST string value being validated, such as one.example.com.
 *   Note that this is checked for uniqueness separately. This value is not
 *   modifiable.
 * @return
 *   No return value. Modify $error_list by reference. Return an empty array
 *   or NULL to validate this domain.
 *
 * @see domain_valid_domain()
 */
function hook_domain_validate_alter(&$error_list, $subdomain) {
  // Only allow TLDs to be .org for our site.
  if (substr($subdomain, -4) != '.org') {
    $error_list[] = t('Only .org domains may be registered.');
  }
}

/**
 * Allow modules to change the status of the 'domain_all' grant.
 *
 * hook_domain_grant_all_alter() fires _after_ Domain Access has
 * determined if a page should ignore Domain Access rules or not. It
 * can be used to extend the core functionality. For a use-case see the
 * discussion about auto-complete callbacks.
 *
 * @link http://drupal.org/node/842338
 *
 * Note that granting access may introduce security issues,
 * so module authors need to be very aware of the conditions that should
 * trigger a TRUE response.
 *
 * Also note that the status of this function cannot be changed _during_ a
 * page load. Drupal's Node Access system only allows these permissions
 * to be set once per callback.
 *
 * @param $grant
 *   A boolean value. FALSE indicates that Domain Access rules should
 *   be enforced. TRUE indicates to ignore Domain Access.
 * @param $options
 *   An array of optional information gathered by domain_grant_all(). This
 *   keyed array may contain the following values:
 *    'script' == The name of invoking script if the page is called by cron.php
 *      or xmlrpc.php instead of Drupal's standard index.php. Presence indicates
 *      that the function returned TRUE.
 *    'search' == Indicates that we are on a search page and searching across
 *      all domains has been enabled.
 *    'pages' == The matching pattern list for page-specific access.
 *    'page_match' == Indicates that one of the page-specific matches returned
 *      TRUE.
 * @return
 *   No return value. Alter $grant by reference.
 *
 * @see domain_grant_all()
 */
function hook_domain_grant_all_alter(&$grant, $options) {
  // Always show all nodes on admin pages.
  $base_path = arg(0);
  if ($base_path == 'admin') {
    $grant = TRUE;
  }
}

/**
 * Allows content or users to be reassigned to a new domain.
 *
 * @param $old_domain
 *   The current domain record, most commonly passed during a domain deletion.
 * @param $new_domain
 *   The target domain record.
 * @param $table
 *   The database table being affected. This value indicates the type of update
 *   being performed. Core module values are 'domain_access' (indicating that
 *   node records are being affected, and 'domain_editor' (indicating user
 *   records).
 *
 * @return
 *   No return value.
 */
function hook_domain_reassign($old_domain, $new_domain, $table) {
  // On node changes, update {domain_source}.
  if ($table == 'domain_access') {
    db_update('domain_source')
      ->fields(array('domain_id' => $new_domain['domain_id']))
      ->condition('domain_id', $old_domain['domain_id'])
      ->execute();
  }
}

/**
 * Notifies modules that an inactive domain has been requested.
 *
 * Modules may choose to allow access to specific paths for purposes such as
 * login.
 *
 * @param $path
 *   The path requested.
 * @param $_domain
 *   The active domain request. Identical to domain_get_domain().
 * @param $account
 *   The user account making the request.
 *
 * @return
 *   TRUE if access should be allowed. FALSE in all other cases.
 */
function hook_domain_invalid_request($path, $_domain, $account) {
  if ($path == 'user/login') {
    return TRUE;
  }
  return FALSE;
}
