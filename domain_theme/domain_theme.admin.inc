<?php
// $Id$

/**
 * @file
 * Include file to handle theme configration screen
 *
 * @ingroup domain_theme
 */

/**
 * The domain theme page callback router.
 *
 * @param $domain
 *  The $domain object created by domain_lookup().
 */
function domain_theme_page($domain) {
  if (isset($domain['domain_id'])) {

    // Load the system form file.
    include_once drupal_get_path('module', 'system') .'/system.admin.inc';

    // Set the page title and generate the form.
    drupal_set_title(t('!site : Domain theme settings', array('!site' => $domain['sitename'])));
    return drupal_get_form('domain_theme_form', $domain);
  }
  else {
    return t('Invalid domain request.');
  }
}

function domain_theme_form($form, &$form_state, $domain) {
  $form = array();
  // Get the current $theme for this domain, if available.
  $theme = domain_theme_lookup($domain['domain_id']);
  if ($theme['theme']) {
    $default = $theme['theme'];
  }
  else {
    $default = variable_get('theme_default', 'garland');
    if (empty($_POST)) {
      drupal_set_message(t('No theme has been set for this domain.'));
    }
  }
  // Message to users.
  $form['intro'] = array(
    '#markup' => t('<p>Select the default theme for this domain. You may only select themes <a href="!url">activated for all sites</a>.</p>', array('!url' => url('admin/appearance'))) . theme_domain_theme_reset(array('domain' => $domain)),
  );
  // Which domain are we editing?
  $form['domain_id'] = array(
    '#type' => 'value',
    '#value' => $domain['domain_id'],
  );
  $themes = _system_theme_list();
  $form['theme'] = array(
    '#tree' => TRUE,
    '#description' => t('To enable additional themes, <a href="!url">configure them globally</a>', array('!url' => url('admin/appearance'))),
  );
  foreach ($themes as $key => $theme) {
    if ($theme->status) {
      $form['theme'][$key] = array(
        '#type' => 'radio',
        '#return_value' => $key,
        '#parents' => array('theme'),
        '#default_value' => ($key == $default) ? $key : FALSE,
      );
    }
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Set domain theme'),
  );
  return $form;
}


function domain_theme_form_submit($form, &$form_state) {
  // Update or Insert?
  $domain_id = $form_state['values']['domain_id'];
  $theme = $form_state['values']['theme'];
  // Set all themes in this domain to null status.
  db_update('domain_theme')
    ->fields(array(
      'status' => 0,
    ))
    ->condition('domain_id', $domain_id)
    ->execute();
  // Now activate the selected theme.
  // This lookup returns -1 on failure.
  $check = domain_theme_lookup($domain_id, $theme);
  // Update.
  if ($check != -1) {
    db_update('domain_theme')
      ->fields(array(
        'status' => 1,
      ))
      ->condition('domain_id', $domain_id)
      ->condition('theme', $theme)
      ->execute();
  }
  // Insert.
  else {
    db_insert('domain_theme')
      ->fields(array(
        'domain_id' => $domain_id,
        'theme' => $theme,
        'settings' => '',
        'status' => 1,
        'filepath' => '',
      ))
      ->execute();
  }
  // Return to the correct page.
  $form_state['redirect'] = 'admin/config/domain/theme/' . $domain_id;
  // Clear the cache.
  cache_clear_all();
}
/**
 * FormsAPI theming.
 */
function theme_domain_theme_form($variables) {
  $form = $variables['form'];
  $output = '';
  $output .= drupal_render($form['intro']);
  $themes = _system_theme_list();
  $header = array(t('Screenshot'), t('Theme'), t('Default'), t('Options'));
  $rows = array();
  foreach (element_children($form['theme']) as $key) {
    $default = '';
    if ($form['theme'][$key]['#value'] == $key) {
      $default = t('(default theme)');
    }
    $row = array(
      theme('image', array('path' => $themes[$key]->info['screenshot'])),
      '<h3>' . check_plain($themes[$key]->info['name'] . ' ' . $themes[$key]->info['version']) . ' ' . $default .  '</h3>' . check_plain($themes[$key]->info['description']),
      drupal_render($form['theme'][$key]),
      l('configure', 'admin/config/domain/theme/'. $key .'/'. $form['domain_id']['#value'] .'/theme-settings')
    );
    $rows[] = $row;
  }

  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Resets theme settings by removing the domain row from {domain_theme}.
 *
 * @param $domain
 * The $domain object created by domain_lookup().
 * @return
 * A confirmation form.
 */
function domain_theme_reset($domain) {
  if ($domain == -1) {
    return t('An invalid request has been made.');
  }
  return drupal_get_form('domain_theme_reset_form', $domain);
}

/**
 * FormsAPI for resetting a domain themes.
 *
 * @param $domain
 * The $domain object for the selected domain.
 * @return
 * Themed HTML form.
 */
function domain_theme_reset_form($form, $form_state, $domain) {
  $extra['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form = confirm_form($extra, t('Are you sure you wish to reset the theme for %name?', array('%name' => $domain['sitename'])), 'admin/config/domain/theme/'. $domain['domain_id'], t('Submitting this form will restore default theme for this domain.'));
  return $form;
}

/**
 * FormsAPI for domain_theme_reset_form.
 */
function domain_theme_reset_form_submit($form, &$form_state) {
  db_delete('domain_theme')
    ->condition('domain_id', $form_state['values']['domain_id'])
    ->execute();
  drupal_set_message(t('Domain theme settings have been reset.'));
  $form_state['redirect'] = 'admin/config/domain/theme/'. $form_state['values']['domain_id'];
  // Clear the cache.
  cache_clear_all();
}

/**
 * Theme a message at the top of domain theme pages.
 *
 * @param $domain
 * The $domain object for the selected domain.
 * @return
 * Themed HTML messages.
 */
function theme_domain_theme_reset($variables) {
  $domain = $variables['domain'];
  $output = '';
  $output .= '<p>'. t('These settings will replace your default site theme when %name is the active domain.', array('%name' => $domain['sitename'])) .'</p>';
  $data = db_query("SELECT theme FROM {domain_theme} WHERE domain_id = :domain_id", array(':domain_id' => $domain['domain_id']))->fetchAssoc();
  if (!empty($data)) {
    $output .= '<p>'. t('You may <a href="!url">erase these settings</a> to restore the default behavior.', array('!url' => url('admin/config/domain/theme-reset/'. $domain['domain_id']))) .'</p>';
  }
  return $output;
}

/**
 * Implements hook_form_alter().
 *
 * This function is a helper to a normal hook_form_alter implementation,
 * where we add additonal form elemtns if we are dealing with domain-specific
 * form settings.
 */
function domain_theme_form_alter(&$form, &$form_state, $form_id) {
  // We cannot use a named form_alter here because of color module.
  // Color submit must happen first, and a named function destroys that.
  if ($form_id != 'system_theme_settings') {
    return;
  }
  $theme = arg(4);
  $domain_id = arg(5);
  $themes = list_themes();

  $domain = domain_load($domain_id);
  if ($domain == -1 || !array_key_exists($theme, $themes)) {
    return drupal_access_denied();
  }

  drupal_set_title(t('!site : %theme settings', array('!site' => $domain['sitename'], '%theme' => $theme)));
  // Which domain are we editing?
  $form['domain_id'] = array(
    '#type' => 'value',
    '#value' => $domain['domain_id'],
  );
  $form['theme'] = array(
    '#type' => 'value',
    '#value' => $theme,
  );

   // We have to remove the core submit handler, but keep other handlers.
  $form['#submit'][100] = 'domain_theme_settings_submit';
  foreach ($form['#submit'] as $key => $value) {
    if ($value == 'system_theme_settings_submit') {
      unset($form['#submit'][$key]);
    }
  }
  ksort($form['#submit']);

  // Check for the presence of color.module.
  $color_info = NULL;
  if (module_exists('color')) {
    $color_info = color_get_info($theme);
  }
  if (empty($color_info)) {
    return;
  }

  // Color module will reset the values in {variable}. which we don't
  // want to happen. So we have to grab the existing values and store
  // them so that we can set the {variable} table correctly.
  // TODO: Make this work with Domain Prefix.
  $vars = array('palette', 'stylesheets', 'logo', 'files', 'screenshot');
  foreach ($vars as $variable) {
    $name = 'color_'. $theme .'_'. $variable;
    $value = db_result(db_query("SELECT value FROM {variable} WHERE name = '%s'", $name));
    $color_settings[$name] = isset($value) ? $value : NULL;
  }
  $form['domain_color_defaults'] = array('#type' => 'value', '#value' => $color_settings);
}

/**
 * The domain theme page callback router.
 *
 * @param $theme
 *  The theme being configured.
 * @param $domain
 *  The $domain object created by domain_lookup().
 */
function domain_theme_settings($theme, $domain) {
  // Load the system form file.
  include_once(drupal_get_path('module', 'system') .'/system.admin.inc');

  $settings = db_fetch_array(db_query("SELECT theme, settings FROM {domain_theme} WHERE domain_id = %d AND theme = '%s'", $domain['domain_id'], $theme));
  drupal_set_message(t('You are viewing the %theme settings for %domain.', array('%theme' => $theme, '%domain' => $domain['sitename'])));
  // Write uploads to the proper directory.
  if ($domain['domain_id'] > 0) {
    global $conf;
    $conf['file_directory_path'] = file_directory_path() .'/domain-'. $domain['domain_id'];
  }

  // If there are settings, we have to load ours.
  if (!empty($settings)) {
    domain_theme_set_variables($settings);
    return drupal_get_form('system_theme_settings', $settings['theme']);
  }
  else {
    return drupal_get_form('system_theme_settings', $theme);
  }
}

/**
 * Process domain_theme_settings form submissions.
 */
function domain_theme_settings_submit($form, &$form_state) {
  $values = $form_state['values'];
  $filepath = NULL;
  $reset = FALSE;
  $form_state['redirect'] = 'admin/config/domain/theme/'. $values['domain_id'];
  $domain = domain_lookup($values['domain_id']);
  if ($values['op'] == $values['reset']) {
    db_query("DELETE FROM {domain_theme} WHERE domain_id = %d AND theme = '%s'", $values['domain_id'], $values['theme']);
    drupal_set_message(t('The %theme settings for %domain have been reset.', array('%theme' => $values['theme'], '%domain' => $domain['sitename'])));
    $reset = TRUE;
  }

  $vars = array('palette', 'stylesheets', 'logo', 'files', 'screenshot');
  foreach ($vars as $variable) {
    $preset = variable_get('color_'. $values['theme'] .'_'. $variable, '');
    if (!empty($preset)) {
      $values['color_'. $values['theme'] .'_'. $variable] = $preset;
    }
  }
  // If our domain uses different schemes, we have to ensure that the {variable} table stays accurate
  // for the primary domain.
  if (isset($values['domain_color_defaults'])) {
    foreach ($values['domain_color_defaults'] as $key => $value) {
      if (!empty($value)) {
        variable_set($key, domain_unserialize($value));
      }
      else {
        variable_del($key);
      }
    }
  }
  // Set the filepath for color module.
  if (!empty($values['color_'. $values['theme'] .'_stylesheets'][0])) {
    $filepath = domain_theme_get_color_path($values['color_'. $values['theme'] .'_stylesheets'][0]);
  }

  // If a reset, stop here.
  if ($reset) {
    return;
  }

  $key = $values['var'];
  $domain_id = $values['domain_id'];
  $theme = $values['theme'];
  // If no record exists, we behave differently.
  $check = db_result(db_query("SELECT COUNT(*) FROM {domain_theme} WHERE domain_id = %d AND theme = '%s'", $domain_id, $theme));
  if ($values['op'] == $values['reset'] && $check > 0) {
    db_query("UPDATE {domain_theme} SET settings = NULL, filepath = NULL WHERE domain_id = %d AND theme = '%s'", $domain_id, $theme);
    drupal_set_message(t('The configuration options have been reset to their default values.'));
  }
  else {
    // Exclude unnecessary elements before saving.
    unset($values['var'], $values['submit'], $values['reset'], $values['form_id'], $values['op'], $values['form_build_id'], $values['form_token'], $values['domain_id'], $values['domain_color'], $values['domain_color_defaults']);
    $settings = serialize($values);
    if ($check > 0) {
      db_query("UPDATE {domain_theme} SET settings = %b, filepath = '%s' WHERE domain_id = %d AND theme = '%s'", $settings, $filepath, $domain_id, $theme);
    }
    else {
      db_query("INSERT INTO {domain_theme} (domain_id, theme, settings, status, filepath) VALUES (%d, '%s', %b, 0, '%s')", $domain_id, $theme, $settings, $filepath);
    }
    drupal_set_message(t('The configuration options have been saved.'));
  }
  // If nothing is active, then we make this one active.
  $active = db_result(db_query("SELECT COUNT(*) FROM {domain_theme} WHERE domain_id = %d AND status = 1", $domain_id));
  if (empty($active)) {
    db_query("UPDATE {domain_theme} SET status = 1 WHERE domain_id = %d AND theme = '%s'", $domain_id, $theme);
    drupal_set_message(t('%theme has been set as the default theme for %domain', array('%theme' => $theme, '%domain' => $domain['sitename'])));
  }
  // Clear the cache.
  cache_clear_all();
}
