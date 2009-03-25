<?php
// $Id$

/**
 * @file
 * Domain manager configuration options.
 *
 * Provides admin screens for configuration overrides.
 * @ingroup domain_conf
 */

/**
 * The domain conf page callback router.
 *
 * @param $domain
 *  The $domain object created by domain_lookup().
 * @return
 * The appropriate form or an error message.
 */
function domain_conf_page($domain) {
  if ($domain == -1) {
    return t('Invalid page requested.');
  }

  // Load the system form file.
  include_once drupal_get_path('module', 'system') .'/system.admin.inc';

  $output = theme_domain_conf_reset($domain);
  if ($domain['domain_id'] > 0) {
    drupal_set_title(t('!site : Domain site settings', array('!site' => $domain['sitename'])));
    return $output . drupal_get_form('system_site_information_settings');
  }
  else if ($domain['domain_id'] == 0) {
    return $output . drupal_get_form('domain_conf_default', $domain);
  }
}

/**
 * Special configuration options for the main domain.
 *
 * @param $domain
 * The $domain object for the default domain.
 * @return
 * A $form array according to the FormsAPI, if unique configuration is possible.
 */

function domain_conf_default($domain) {
  drupal_set_title(t('!site : Domain site settings', array('!site' => $domain['sitename'])));
  $form = array();
  // Grab any extra elements defined by other modules.
  $extra = domain_conf_api();
  if (!empty($extra)) {
    // Convert the $extra array to the $form array.
    $form = $extra;
    $form['domain_conf_message'] = array(
      '#type' => 'markup',
      '#value' => t('<p>The following custom settings may be applied to the main domain.  These options are specific to the Domain module and do not have standard configuration pages.</p>'),
      '#weight' => -100,
    );
    // Domain information, for saving.
    $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
    // Submit functions
    $form['#submit'][] = 'domain_conf_form_submit';
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save domain settings'),
      '#weight' => 10
    );
  }
  else {
    $form['domain_conf_message'] = array('#type' => 'markup', '#value' => t('There are no custom domain settings to configure.'));
  }
  return $form;
}

/**
 * The bulk of our hook_form_alter implementation.
 */
function domain_conf_system(&$form, &$form_state, $form_id) {
  // We use the system_site_information_settings form as a base, and add the elements we need
  // from other forms.  The default values are altered based on stored settings.
  $domain_id = arg(4);
  $domain = domain_lookup($domain_id);
  $data = db_result(db_query("SELECT settings FROM {domain_conf} WHERE domain_id = %d", $domain['domain_id']));
  if (!empty($data)) {
    $settings = unserialize($data);
  }
  else {
    $settings = array();
  }
  $unset = array('buttons', '#submit');
  foreach ($unset as $key) {
    unset($form[$key]);
  }
  $form['main'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain information'),
    '#collapsible' => TRUE,
    '#weight' => -10
  );
  // Put the defaults in the fieldset
  $fields = array('site_name', 'site_mail', 'site_slogan', 'site_mission', 'site_footer', 'site_frontpage', 'anonymous');
  foreach ($fields as $field) {
    $form['main'][$field] = $form[$field];
    unset($form[$field]);
  }

  // Change the path for the frontpage.
  $prefix = $form['main']['site_frontpage']['#field_prefix'];
  $_path = parse_url($prefix);
  $str = $_path['host'];
  $fix = preg_replace("/$str/", $domain['subdomain'], $prefix, 1);
  $form['main']['site_frontpage']['#field_prefix'] = $fix;

  // Admin theme settings
  $themes = list_themes();
  ksort($themes);
  $options[] = t('Use domain default theme');
  foreach ($themes as $key => $value) {
    $options[$key] = $key;
  }
  $form['main']['admin_theme'] = array(
    '#type' => 'select',
    '#title' => t('Administrative theme'),
    '#options' => $options,
    '#default_value' => variable_get('admin_theme', '0'),
  );

  // Menu settings: primary and secondary links.
  $form['menu'] = array(
    '#type' => 'fieldset',
    '#title' => t('Menu settings'),
    '#collapsible' => TRUE,
    '#weight' => -6
  );

  $menus = menu_get_menus();

  $form['menu']['menu_default_node_menu'] = array(
    '#type' => 'select',
    '#title' => t('Default menu for content'),
    '#default_value' => isset($settings['menu_default_node_menu']) ? $settings['menu_default_node_menu'] : variable_get('menu_default_node_menu', 'primary-links'),
    '#options' => $menus,
    '#description' => t('Choose the menu to be the default in the menu options in the content authoring form.'),
  );

  $menus[0] = t('Do not use for this site');
  $form['menu']['menu_primary_links_source'] = array(
    '#type' => 'select',
    '#title' => t('Primary links menu'),
    '#default_value' => isset($settings['menu_primary_links_source']) ? $settings['menu_primary_links_source'] : variable_get('menu_primary_links_source', 'primary-links'),
    '#options' => $menus,
    '#description' => t('Select the primary links for this site.')
  );

  $form['menu']['menu_secondary_links_source'] = array(
    '#type' => 'select',
    '#title' => t('Secondary links menu'),
    '#default_value' => isset($settings['menu_secondary_links_source']) ? $settings['menu_secondary_links_source'] : variable_get('menu_secondary_links_source', 'secondary-links'),
    '#options' => $menus,
    '#description' => t('Select the secondary links for this site.')
  );

  // Date settings: set the default timezone
  $form['date'] = array(
    '#type' => 'fieldset',
    '#title' => t('Timezone settings'),
    '#collapsible' => TRUE,
    '#weight' => -5
  );
  $zones = _system_zonelist();
  $form['date']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => isset($settings['date_default_timezone']) ? $settings['date_default_timezone'] : variable_get('date_default_timezone', 0),
    '#options' => $zones,
    '#description' => t('Select the default site time zone.')
  );

  // Language settings, if needed.
  if (module_exists('locale') && variable_get('language_count', 1) > 1) {
    $form['language'] = array(
      '#type' => 'fieldset',
      '#title' => t('Language settings'),
      '#collapsible' => TRUE,
      '#weight' => 0,
    );
    $options = domain_conf_language_options();
    $default = language_default();
    $form['language']['language_default'] = array(
      '#type' => 'radios',
      '#title' => t('Default language'),
      '#options' => $options,
      '#default_value' => isset($settings['language_default']) ? $settings['language_default'] : $default->language,
      '#description' => t('The default language to use for this domain. <em>Note: This setting only works with path-based language switching.</em>'),
    );
  }

  // Offline notices.
  $form['offline'] = array(
    '#type' => 'fieldset',
    '#title' => t('Maintenance settings'),
    '#collapsible' => TRUE,
    '#weight' => 5
  );
  $form['offline']['site_offline'] = array(
    '#type' => 'radios',
    '#title' => t('Site status'),
    '#default_value' => isset($settings['site_offline']) ? $settings['site_offline'] : variable_get('site_offline', 0),
    '#options' => array(t('Online'), t('Off-line')),
    '#description' => t('When set to "Online", all visitors will be able to browse your site normally. When set to "Off-line", only users with the "administer site configuration" permission will be able to access your site to perform maintenance; all other visitors will see the site off-line message configured below. Authorized users can log in during "Off-line" mode directly via the <a href="@user-login">user login</a> page.', array('@user-login' => url('user'))),
  );

  $form['offline']['site_offline_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Site off-line message'),
    '#default_value' => isset($settings['site_offline_message']) ? $settings['site_offline_message'] : variable_get('site_offline_message', t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => $domain['sitename']))),
    '#description' => t('Message to show visitors when the site is in off-line mode.')
  );

  // Site name must be edited at the domain creation screen.
  $form['main']['site_name']['#disabled'] = TRUE;
  $form['main']['site_name']['#description'] = t('The name of this web site, as entered in the <a href="!url">domain-specific settings</a>.', array('!url' => url('admin/build/domain/edit/'. $domain['domain_id'])));

  // Reset the provided form defaults, if needed
  $form['main']['site_name']['#value'] = $domain['sitename'];
  $form['main']['site_mail']['#default_value'] = isset($settings['site_mail']) ? $settings['site_mail'] : variable_get('site_mail', ini_get('sendmail_from'));
  $form['main']['site_slogan']['#default_value'] = isset($settings['site_slogan']) ? $settings['site_slogan'] : variable_get('site_slogan', '');
  $form['main']['site_mission']['#default_value'] = isset($settings['site_mission']) ? $settings['site_mission'] : variable_get('site_mission', '');
  $form['main']['site_footer']['#default_value'] = isset($settings['site_footer']) ? $settings['site_footer'] : variable_get('site_footer', '');
  $form['main']['site_frontpage']['#default_value'] = isset($settings['site_frontpage']) ? $settings['site_frontpage'] : variable_get('site_frontpage', 'node');
  $form['main']['anonymous']['#default_value'] = isset($settings['anonymous']) ? $settings['anonymous'] : variable_get('anonymous', t('Guest'));

  // Domain information, for saving.
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);

  // Grab any extra elements defined by other modules.
  $extra = domain_conf_api(TRUE);
  // Merge the $extra and $form arrays.
  $form = array_merge($form, $extra);

  // Submit functions
  $form['#submit'][] = 'domain_conf_form_submit';
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save domain settings'),
    '#weight' => 10
  );
}

/**
 * FormsAPI for domain_conf_form().
 */
function domain_conf_form_submit($form, &$form_state) {
  // Throw away what we don't need.
  $settings = $form_state['values'];
  $unset = array('form_token', 'form_id', 'form_build_id', 'op', 'submit');
  foreach ($unset as $key) {
    unset($settings[$key]);
  }
  // INSERT or UPDATE?
  $check = 0;
  $check = db_result(db_query("SELECT COUNT(domain_id) FROM {domain_conf} WHERE domain_id = %d", $form_state['values']['domain_id']));
  if ($check > 0) {
    $sql = "UPDATE {domain_conf} SET settings = %b WHERE domain_id = %d";
    db_query($sql, serialize($settings), $form_state['values']['domain_id']);
  }
  else {
    $sql = "INSERT INTO {domain_conf} (domain_id, settings) VALUES (%d, %b)";
    db_query($sql, $form_state['values']['domain_id'], serialize($settings));
  }
  drupal_set_message(t('Domain options saved successfully.'));
}

/**
 * Resets configuration settings by removing the domain row from {domain_conf}.
 *
 * @param $domain
 *  The $domain object created by domain_lookup().
 * @return
 * A confirmation form.
 */

function domain_conf_reset($domain) {
  if ($domain == -1) {
    return t('Invalid page requested.');
  }
  return drupal_get_form('domain_conf_reset_form', $domain);
}

/**
 * FormsAPI for resetting a domain configuration.
 *
 * @param $domain
 * The $domain object for the selected domain.
 * @return
 * Themed HTML form.
 */

function domain_conf_reset_form($form_state, $domain) {
  $extra['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form = confirm_form($extra, t('Are you sure you wish to reset the settings for %name?', array('%name' => $domain['sitename'])), 'admin/build/domain/conf/'. $domain['domain_id'], t('Submitting this form will restore default settings for this domain.'));
  return $form;
}

/**
 * FormsAPI for domain_conf_reset_form().
 */
function domain_conf_reset_form_submit($form, &$form_state) {
  db_query("DELETE FROM {domain_conf} WHERE domain_id = %d", $form_state['values']['domain_id']);
  drupal_set_message(t('Domain configuration settings have been reset.'));
  $form_state['redirect'] = 'admin/build/domain/conf/'. $form_state['values']['domain_id'];
}

/**
 * Theme a message at the top of domain configuration pages.
 *
 * @param $domain
 * The $domain object for the selected domain.
 * @return
 * Themed HTML messages.
 */
function theme_domain_conf_reset($domain) {
  $output = '';
  $output .= '<p>'. t('These settings will replace or supplement your default site settings when %name is the active domain.', array('%name' => $domain['sitename'])) .'</p>';
  $data = db_fetch_array(db_query("SELECT settings FROM {domain_conf} WHERE domain_id = %d", $domain['domain_id']));
  if (!empty($data)) {
    $output .= '<p>'. t('You may <a href="!url">erase these settings</a> to restore the default behavior.', array('!url' => url('admin/build/domain/conf-reset/'. $domain['domain_id']))) .'</p>';
  }
  return $output;
}
