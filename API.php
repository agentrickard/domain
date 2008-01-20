<?php
// $Id$

/**
 * @file
 * API documentation file.
 */

/**
 * @defgroup drupal Drupal core
 * Core Drupal hooks.
 */

/**
 * @defgroup node Node hooks
 * Drupal node and node_access hooks.
 */

/**
 * @defgroup domain Domain Access module
 * Core functions for the Domain Access module.
 */

/**
 * @defgroup conf Domain Conf: configuration extension
 * Core functions for the Domain Conf module.
 */
 
 /**
  * @defgroup hooks Domain API hooks
  * Internal module hooks for Domain Access.
  */

/**
 * @defgroup nav Domain Navigation: navigation block and menu options
 * Configurable navigation and block based on active domains.
 */

/**
 * @defgroup content Domain Content : administer nodes
 * Configurable navigation block based on active domains.
 */
 
/**
 * @defgroup theme Domain Theme: manage themes
 * Switch themes based on active domain.
 */

/**
 * @defgroup themes Theme functions
 * Theme functions used by the Domain Access modules.
 */

/**
 * @defgroup user Domain User: personal subdomains
 * Creates unique subdomains for registered users.
 */

/**
 * @defgroup views Domain Views: views integration
 * Provides a Views filter for the Domain Access module.
 */

/**
 * @defgroup strict Domain Strict: strict access control
 * Forces users to be assigned to a domain in order to view content on that domain.
 */ 
 
 /**
  * @mainpage
   Welcome to the API documentation for the Domain Access module, I hope you find it useful.
  
   If you find errors in the documentation, please file an issue at http://drupal.org/project/issues/domain.
  
   -- agentrickard
  */

/**
 * Notify other modules that we are granting access to a node.
 *
 * This hook allows Domain Access modules to overwrite default behaviors.
 * See http://api.drupal.org/api/function/hook_node_grants/5 for more detail.
 *
 * @see domain_strict_domaingrants() for example usage.
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
 * @ingroup hooks
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
 * @ingroup hooks
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
 *  The modified $domain array.
 *
 * @ingroup hooks
 */
function hook_domainload($domain) {
  // Add a variable to the $domain array.
  $domain['myvar'] = 'mydomainvar';
  // Remove the site_grant flag, making it so users can't see content for 'all affiliates.'
  $domain['site_grant'] = FALSE;
  return $domain;
}
  
/**
 * Notify other modules that we have created a new domain or 
 * updated a domain record.  
 *
 * NOTE: Where possible, use the $domain values in preference to the $edit values.
 *
 * @param $op
 *  The operation being performed: 'create', 'update', 'delete'
 * @param $edit
 *  The form values processed by the form.
 *
 * @ingroup hooks
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
 * @ingroup hooks
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
 * @ingroup hooks
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
 * @ingroup hooks
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
 * @see domain_conf_domaininstall() for an example.
 *
 * @ingroup hooks 
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
 * @see domain_user_domaininfo() for an example.
 *
 * @ingroup hooks
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
 * @ingroup hooks
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
 * @param $domain
 *  The $domain object prepared by hook_domainload().
 * @return
 *  A $form array element as defined by the FormsAPI.
 *
 *  @ingroup hooks 
 */
function hook_domainconf($domain) {
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