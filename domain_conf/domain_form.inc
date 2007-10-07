<?php
// $Id$

/**
 * @file
 * Domain configuration functions.
 *
 * This file is loaded as needed.  It replicates the system_settings_form and adds a few
 * necessary elements so that each domain can have unique settings.
 */

/**
 * Implements hook_form_alter()
 *
 * Since this function is only loaded at the path admin/build/domain/conf, we
 * don't have to worry about hook_form_alter() being called when not wanted.
 *
 * @ingroup conf
 */
function domain_conf_form_alter($form_id, &$form) {
  // We use the system_site_information_settings form as a base, and add the elements we need
  // from other forms.  The default values are altered based on stored settings.
  if ($form_id == 'system_site_information_settings') {
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
    $fields = array('site_name', 'site_mail', 'site_slogan', 'site_mission', 'site_footer', 'site_frontpage', 'anonymous', 'xcoord', 'ycoord');
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
      '#default_value' => isset($settings['date_default_timezone']) ? $settings['date_default_timezone'] :variable_get('date_default_timezone', 0),
      '#options' => $zones,
      '#description' => t('Select the default site time zone.')
    );    
    
    // File system settings -- this is untested!
    $form['file'] = array(
      '#type' => 'fieldset',
      '#title' => t('File upload settings'),
      '#collapsible' => TRUE,
      '#weight' => 0
    );      
    $form['file']['file_directory_path'] = array(
      '#type' => 'textfield',
      '#title' => t('File system path'),
      '#default_value' => file_directory_path(),
      '#maxlength' => 255,
      '#description' => t('A file system path where the files will be stored. This directory has to exist and be writable by Drupal. If the download method is set to public this directory has to be relative to the Drupal installation directory, and be accessible over the web. When download method is set to private this directory should not be accessible over the web. Changing this location after the site has been in use will cause problems so only change this setting on an existing site if you know what you are doing.'),
      '#after_build' => array('system_check_directory'),
    );
    $form['file']['file_directory_temp'] = array(
      '#type' => 'textfield',
      '#title' => t('Temporary directory'),
      '#default_value' => file_directory_temp(),
      '#maxlength' => 255,
      '#description' => t('Location where uploaded files will be kept during previews. Relative paths will be resolved relative to the Drupal installation directory.'),
      '#after_build' => array('system_check_directory'),
    );
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
      '#default_value' => isset($settings['site_offline']) ? $settings['site_offline'] :variable_get('site_offline', 0),
      '#options' => array(t('Online'), t('Off-line')),
      '#description' => t('When set to "Online", all visitors will be able to browse your site normally. When set to "Off-line", only users with the "administer site configuration" permission will be able to access your site to perform maintenance; all other visitors will see the site off-line message configured below. Authorized users can log in during "Off-line" mode directly via the <a href="@user-login">user login</a> page.', array('@user-login' => url('user'))),
    );
  
    $form['offline']['site_offline_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Site off-line message'),
      '#default_value' => isset($settings['site_offline_message']) ? $settings['site_offline_message'] :variable_get('site_offline_message', t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => isset($settings['site_name']) ? $settings['site_name'] : $domain['sitename']))),
      '#description' => t('Message to show visitors when the site is in off-line mode.')
    );    
    
    // Reset the provided form defaults, if needed
    $form['main']['site_name']['#default_value'] = isset($settings['site_name']) ? $settings['site_name'] : $domain['sitename'];
    $form['main']['site_mail']['#default_value'] = isset($settings['site_mail']) ? $settings['site_mail'] : variable_get('site_mail', ini_get('sendmail_from'));
    $form['main']['site_slogan']['#default_value'] = isset($settings['site_slogan']) ? $settings['site_slogan'] : variable_get('site_slogan', '');    
    $form['main']['site_mission']['#default_value'] = isset($settings['site_mission']) ? $settings['site_mission'] : variable_get('site_mission', '');    
    $form['main']['site_footer']['#default_value'] = isset($settings['site_footer']) ? $settings['site_footer'] : variable_get('site_footer', '');    
    $form['main']['site_frontpage']['#default_value'] = isset($settings['site_frontpage']) ? $settings['site_frontpage'] : variable_get('site_frontpage', 'node');    
    $form['main']['anonymous']['#default_value'] = isset($settings['anonymous']) ? $settings['anonymous'] : variable_get('anonymous', t('Guest'));    
    
    // Domain information, for saving.
    $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);

    // Submit functions
    $form['#submit']['domain_conf_form_submit'] = array();
    $form['#validate']['domain_conf_form_validate'] = array();      
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save domain settings'),
      '#weight' => 10
    );    
  }
}

/**
 * FormsAPI
 *
 * @ingroup conf
 */
function domain_conf_form_submit($form_id, $form_values) {
  // Throw away what we don't need.
  $settings = $form_values;
  $unset = array('form_token', 'form_id', 'op', 'submit');
  foreach ($unset as $key) {
    unset($settings[$key]);
  }
  // INSERT or UPDATE?
  $check = db_result(db_query("SELECT domain_id FROM {domain_conf} WHERE domain_id = %d", $form_values['domain_id']));  
  if ($check) {
    $sql = "UPDATE {domain_conf} SET settings = %b WHERE domain_id = %d";
    db_query($sql, serialize($settings), $form_values['domain_id']);
  }
  else {
    $sql = "INSERT INTO {domain_conf} VALUES (%d, %b)"; 
    db_query($sql, $form_values['domain_id'], serialize($settings));
  }
  drupal_set_message(t('Domain options saved successfully.'));
}