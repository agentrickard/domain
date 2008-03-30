<?php
// $Id$

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
 * Notify other modules that we are granting access to a node.
 *
 * This hook allows Domain Access modules to overwrite default behaviors.
 * See http://api.drupal.org/api/function/hook_node_grants/5 for more detail.
 *
 * @see domain_strict_domaingrants()
 *
 * @param &$grants
 *  The existing default $grants, passed by reference.
 * @param $account
 *  The user object of the user requesting the node.
 * @param $op
 *  The node operation being performed (view, update, or delete).
 *
 * @return
 *  No return value. Modify the $grants array, passed by reference.
 *
 * @ingroup domain_hooks
 */
function hook_domaingrants(&$grants, $account, $op) {
  // Add a sample grant privilege to let a user see their content at all times.
  $grants['domain_example'][] = $account->uid;
}


/**
 * Notify other modules that we are saving node access records.
 *
 * This hook allows Domain Access modules to overwrite the default bahaviors.
 * See http://api.drupal.org/api/function/hook_node_access_records/5 for more detail.
 *
 * @param &$grants
 *  The existing default $grants, passed by reference.
 * @param $node
 *  The node object being saved.
 *
 * @return
 *  No return value. Modify the $grants array, passed by reference.
 *
 *
 * @ingroup domain_hooks
 */
function hook_domainrecords(&$grants, $node) {
  // Add a sample access record to let a user see their content at all times.
  $grants[] = array(
    'realm' => 'domain_example',
    'gid' => $node->uid,
    'grant_view' => TRUE,
    'grant_update' => TRUE,
    'grant_delete' => TRUE,
    'priority' => 0,         // If this value is > 0, then other grants will not be recorded
  );
  // Remove the domain_site grant.
  foreach ($grants as $key => $grant) {
    if ($grant['realm'] == 'domain_site') {
      unset($grants[$key]);
    }
  }
}

/**
 * Notifies other modules that we are loading a domain record from the database.
 *
 * Modules may overwrite or add to the $domain array for each subdomain.
 *
 * WARNING: If you need to make revisions to the $_domain global before it is processed
 * by other modules, you must implement hook_init().  Only modules that implement hook_init()
 * are loaded during the creation routine for the $_domain global.  If your module has not
 * been loaded, then hook_domainload() will skip your implementation.
 *
 * When loading lists of domains or generating domain information, either use the proper
 * functions -- domain_default(), domain_lookup(), and domain_domains() -- or invoke this hook.
 *
 * Invoked by domain_lookup() and domain_default().
 *
 * @param &$domain
 *  The current $domain array.
 *
 * @return
 *  No return value.  The $domain array is modified by reference..
 *
 * @ingroup domain_hooks
 */
function hook_domainload(&$domain) {
  // Add a variable to the $domain array.
  $domain['myvar'] = 'mydomainvar';
  // Remove the site_grant flag, making it so users can't see content for 'all affiliates.'
  $domain['site_grant'] = FALSE;
}

/**
 * Notify other modules that we have created a new domain or
 * updated a domain record.
 *
 * For 'update' and 'delete' operations, the $domain array holds the
 * original values of the domain record.  The $edit array will hold the
 * new, replacement values.  This is useful when making changes to
 * records, such as in domain_user_domainupdate().
 *
 * @param $op
 *  The operation being performed: 'create', 'update', 'delete'
 * @param $edit
 *  The form values processed by the form.
 *
 * @ingroup domain_hooks
 */
function hook_domainupdate($op, $domain = array(), $edit = array()) {
  switch ($op) {
    case 'create':
      db_query("INSERT INTO {mytable} (subdomain, sitename) VALUES ('%s', '%s')", $domain['subdomain'], $domain['sitename']);
      break;
    case 'update':
      db_query("UPDATE {mytable} SET subdomain = '%s', sitename = '%s' WHERE domain_id = %d", $domain['subdomain'], $domain['sitename'], $domain['domain_id']);
      break;
    case 'delete':
      db_query("DELETE FROM {mytable} WHERE subdomain = '%s'", $domain['subdomain']);
      break;
  }
}

/**
 * Returns links to additional functions for the Domain Access module's admin screen
 *
 * Note that if your page requires a user_access check other than 'administer domains'
 * you should explictly check permissions before returning the array.
 *
 * @param $domain
 *  An array of data for the active domain, taken from the {domain} table.
 *    - domain_id -- the unique identifier of this domain
 *    - subdomain -- the host path of the url for this domain
 *    - sitename -- the human-readable name of this domain
 *
 * @return
 *  An array of links to append to the admin screen, in the format:
 *    - title -- the link title
 *    - path -- the link path (a Drupal-formatted path)
 *  The data returned by this function will be passed through the l() function.
 *
 * @ingroup domain_hooks
 */
function hook_domainlinks($domain) {
  if (user_access('my permission')) {
    $links[] = array(
      'title' => t('settings'),
      'path' => 'admin/build/domain/myaction/'. $domain['domain_id']
    );
    return $links;
  }
}

/**
 * Enables modules to add additional parameters to the $domain array
 * for use by the Domain Navigation module.
 *
 * Used in cases where custom themes may require extra parameters.
 * This hook is called by domain_nav_render().
 *
 * Default parameters should not be changed; these are:
 *
 *    - domain_id -- the unique identifier of this domain
 *    - subdomain -- the host path of the url for this domain
 *    - sitename -- the human-readable name of this domain
 *    - path -- the link path (a Drupal-formatted path)
 *    - active -- a boolean flag indicating the currently active domain
 *
 * @ingroup domain_hooks
 */
function hook_domainnav($domain) {
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
 * @param $domain
 *  The information for the current domain record, taken from {domain}.
 *
 * @ingroup domain_hooks
 */
function hook_domaincron($domain) {
  // Run a node query.
  $result = db_query_range(db_rewrite_sql("SELECT n.nid FROM {node} n ORDER BY n.changed"), 0, 1);
  $node = db_fetch_object($result);
  // Set a variable for each domain containing the last node updated.
  variable_set('domain_'. $domain['domain_id'] .'_lastnode', $node->nid);
}

/**
 * Some Domain modules require that settings.php be edited to add
 * additional files during the bootstrap process.
 *
 * This hook allows those modules to check to see if they have been installed
 * correctly.  Usually the module is enabled, but the required function is not.
 *
 * @see domain_conf_domaininstall()
 *
 * @ingroup domain_hooks
 */
function hook_domaininstall() {
  // If MyModule is being used, check to see that it is installed correctly.
  if (module_exists('mymodule') && !function_exists('_mymodule_load')) {
    drupal_set_message(t('MyModule is not installed correctly.  Please edit your settings.php file as described in <a href="!url">INSTALL.txt</a>', array('!url' => drupal_get_path('module', 'mymodule') .'/INSTALL.txt')));
  }
}

/**
 * Allows Domain modules to add columns to the domain list view at
 * path 'admin/build/domain/view'.
 *
 * @param $op
 *  The operation being performed.  Valid requests are:
 *    -- 'header' defines a column header according to theme_table.
 *    -- 'select' defines a string of data to be returned.  Must be prefixed.
 *        The {domain} table is prefixed with 'd' -- do not select any columns
 *        from the domain table.  You must not select domain_id from your table.
 *    -- 'join' defines a sql join to use to pull extra data.  To properly enable
 *        sorting of all records, this MUST be a LEFT JOIN.
 *    -- 'data' defines the data to be written in the column for the
 *        specified domain.
 * @param $domain
 *  The $domain object prepared by hook_domainload().
 * @return
 *  Return values vary based on the $op value.
 *    -- 'header' return a $header array formatted as per theme_table().
 *    -- 'select' return a comman-separated list of fields to select from your table.
 *    -- 'join' return a LEFT JOIN statement for connecting your table to the {domain} table.
 *    -- 'data' return a $data element to print in the row.
 *
 * @see domain_user_domaininfo()
 *
 * @ingroup domain_hooks
 */
function hook_domainview($op, $domain = array()) {
  switch ($op) {
    case 'header':
      return array(array('data' => t('MyData'), 'field' => 'my.uid'), array('data' => t('MyName'), 'field' => 'my.name'));
      break;
    case 'select':
      return 'my.uid, my.name';
    case 'join':
      return "LEFT JOIN {mytable} my ON my.domain_id = d.domain_id";
      break;
    case 'data':
      if ($domain['uid']) {
        $account = user_load(array('uid' => $domain['uid']));
        return l($account->name, 'user/'. $account->uid);
      }
      break;
  }
}

/**
 * Allows other modules to add elements to the default Domain settings page.
 *
 * @param &$form
 *  The $form array generated for the Domain settings page.  This must
 *  be passed by reference.
 *  Normally, you should include your form elements inside a new fieldset.
 * @return
 *  No rerturn value.  The $form is modified by reference, as needed.
 */
function hook_domainform(&$form) {
  // Add the form element to the main screen.
  $form['domain_myfmodule'] = array(
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
 *  This hook is implemented by the Domain Conf module.
 *
 * @return
 *  An array of form_id values representing forms that require warnings.
 *
 * @ingroup domain_hooks
 */
function hook_domainwarnings() {
  // These are the forms for variables set by Domain Conf.
  return array(
    'system_admin_theme_settings',
    'system_date_time_settings',
    'system_site_information_settings',
    'system_site_maintenance_settings'
  );
}

/**
 * Allows modules to add additional form elements for saving as domain-specific
 * settings.
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
 * You may wish to pair this hook with hook_domainbatch() to allow the mass update
 * of your settings.
 *
 * If you wish to store settings that are not related to another module, you must pass
 * the following parameter:
 *
 * $form['myform']['#domain_setting'] = TRUE;
 *
 * Doing so will tell Domain Access that no default settings page exists, and that values
 * must be stored for the primary domain.  This feature is useful for creating special data
 * that needs to be associated with a domain record but does not need a separate table.
 *
 * Using the variable override of hook_domainconf() is an alternative to creating a module
 * and database table for use with hook_domainload().
 *
 * For site managers who wish to implement this hook in other modules, but cannot wait for
 * patches, you do not need to hack the code.  Simply put your functions inside a domain_conf.inc
 * file and place that inside the domain_conf directory.  This file should begin with <?php and conform
 * to Drupal coding standards.
 *
 * @param $domain
 *  The $domain object prepared by hook_domainload().
 * @return
 *  A $form array element as defined by the FormsAPI.
 *
 *  @ingroup domain_hooks
 */
function hook_domainconf($domain) {
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
 * needed to create an administrative page and form that will process your settings.
 *
 * For a basic example, see domain_domainbatch().
 *
 * For a more complex example, with custom processing, see domain_theme_domainbatch().
 *
 * The $batch array is formatted according to the following rules:
 *
 * - '#form' [required] An array that defines the form element for this item.  It accepts any
 * values defined by the FormsAPI.  Do not, however, pass the #default_value element
 * here.  That value will be computed dynamically for each domain when the hook is processed.
 *
 *  - '#system_default' [required] Used to fill the #default_value parameter for domains that do not have custom settings.
 *  Typically, this will be the result of a variable_get().  For domain_delete operations, this value should be set to zero (0).
 *
 * - '#meta_description' [required] Used to describe your action to end users.
 *
 * - '#domain_action' [required] Indicates what submit action will be invoked for this setting.  Allowed values are:
 * --- 'domain' == writes the value to the {domain} table.  Normally, contributed modules will not use this option.
 * --- 'domain_conf' == writes the value to the {domain_conf} table.  Use in connection with hook_domainconf().
 * --- 'domain_delete' == used to delete rows from specific tables.  If this is used, the #table value must be present.
 * --- 'custom' == used if you need your own submit handler. Must be paired with a #submit parameter.
 *
 * - '#submit' [optional] Used with the 'custom' #domain_action to define a custom submit handler for the form.  This value
 * should be a valid function name.  It will be passed the $form_values array for processing.
 *
 * - '#validate' [optional] Used to define a validate handler for the form.  This value
 * should be a valid function name.  It will be passed the $form_values array for processing.
 *
 * - '#lookup' [optional] Used with the 'custom' #domain_action to perform a default value lookup against a custom function.
 * This value should be a valid function name.  Your function must accept the $domain array as a parameter.
 *
 * - '#table' [optional] Used with the 'domain_delete' #domain_action to specify which table a row should be deleted from.
 * This value may be a string or an array, if you need to perform multiple deletes.  Deletes are performed against the domain_id
 * of the selected domains.
 *
 * - '#variable' [optional] Used to perform changes for the default domain, which is stored in the {variables} table. If this
 * value is not set, the root domain will not be exposed for batch editing.
 *
 * - '#data_type' [optional] Used to tell the system how to build your data entry query.  Defaults to 'string'; possible values are:
 * --- 'string' == the query will use '%s' to insert the data.
 * --- 'integer' == the query will use %d to insert the data.
 * --- 'float' == the query will use %f to insert the data.
 * --- 'binary' == the query will use %b to insert the data.
 * For more information, see db_query() in the Drupal API documentation.
 *
 * - '#weight' [optional] Used to weight the item in the menu system.  Should normally be set to zero.  Negative values
 * are reserved for use by the core Domain Access module.  The following values are in use:
 * --- (-10) items used by Domain Access core.
 * --- (-8) items used by Domain Configuration.
 * --- (-6) items used by Domain Theme.
 * --- (-2) items reserved for batch delete actions.
 *
 * @ingroup domain_hooks
 */
function hook_domainbatch() {
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
    '#domain_action' => 'domain_conf',
    '#meta_description' => t('Edit my setting value.'),
    '#variable' => 'domain_mysetting',
    '#validate' => 'domain_mysetting_validate',
    '#data_type' => 'string',
    '#weight' => 0,
  );
  return $batch;
}
