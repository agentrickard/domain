<?php

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
  global $custom_theme;
  if (isset($domain['domain_id'])) {
    // Set the custom theme, for display.
    $custom_theme = variable_get('theme_default', 'garland');
    $theme = domain_theme_lookup($domain['domain_id']);
    // The above returns -1 on failure.
    if ($theme != -1) {
      $custom_theme = $theme['theme'];
      drupal_set_message(t('You are viewing the active theme for %domain.', array('%domain' => $domain['sitename'])));
    }

    // Load the system form file.
    include_once drupal_get_path('module', 'system') .'/system.admin.inc';

    drupal_set_title(t('@site : Domain theme settings', array('@site' => $domain['sitename'])));
    $output = theme_domain_theme_reset($domain);
    return $output . drupal_get_form('system_themes_form');
  }
  else {
    return t('Invalid domain request.');
  }
}

/**
 * Implement hook_form_alter().
 *
 * Since we have this in an include file, it is only called
 * by our module's invocation of this form.
 */
function domain_theme_form_system_themes_form_alter(&$form, &$form_state) {
  $domain_id = arg(4);
  $domain = domain_load($domain_id);
  if ($domain == -1) {
    return drupal_access_denied();
  }
  // Get the current $theme for this domain, if available.
  $theme = domain_theme_lookup($domain['domain_id']);
  if ($theme['theme']) {
    $form['theme_default']['#default_value'] = $theme['theme'];
  }
  else {
    $form['theme_default']['#default_value'] = '';
    if (empty($_POST)) {
      drupal_set_message(t('No theme has been set for this domain.'));
    }
  }
  // Unset options that are not allowed.
  $available = $form['status']['#options'];
  $allowed = $form['status']['#default_value'];
  foreach ($available as $key => $value) {
    if (!in_array($key, $allowed)) {
      // If the theme was disabled, then we have to set a new default
      if ($key == $theme['theme']) {
        $form['theme_default']['#default_value'] = '';
        if (empty($_POST)) {
          drupal_set_message(t('The chosen theme is no longer available for this domain.'), 'status', FALSE);
        }
      }
      unset($form[$key]);
      unset($form['status']['#options'][$key]);
      unset($form['theme_default']['#options'][$key]);
    }
    else {
      $form['status']['#disabled'] = TRUE;
    }

    if (isset($form[$key]) && isset($form[$key]['operations'])) {
      $form[$key]['operations']= array(
        '#value' => l(t('configure'), 'admin/build/domain/theme/'. $key .'/'. $domain['domain_id'] .'/theme-settings'),
      );
    }
  }
  // Use our own submit buttons.
  $unset = array('buttons', '#submit');
  foreach ($unset as $key) {
    unset($form[$key]);
  }
  // Message to users.
  $form['intro'] = array(
    '#value' => t('<p>Select the default theme for this domain.</p>'),
  );
  // Which domain are we editing?
  $form['domain_id'] = array(
    '#type' => 'value',
    '#value' => $domain['domain_id'],
  );
  // Our submit handlers.
  $form['#submit'][] = 'domain_theme_submit';
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Set domain theme'),
  );
}

/**
 * Implement hook_form_alter().
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

  drupal_set_title(t('@site : %theme settings', array('@site' => $domain['sitename'], '%theme' => $theme)));
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
 * FormsAPI submit handler for the theme settings
 */
function domain_theme_submit($form, &$form_state) {
  $theme = $form_state['values']['theme_default'];
  $domain_id = $form_state['values']['domain_id'];
  $settings = NULL; // This is a placeholder for advanced functions.
  db_query("UPDATE {domain_theme} SET status = 0 WHERE domain_id = %d", $domain_id);
  $check = domain_theme_lookup($domain_id, $theme);
  if ($check == -1) {
    db_query("INSERT INTO {domain_theme} (domain_id, theme, settings, status) VALUES (%d, '%s', %b, 1)", $domain_id, $theme, $settings);
  }
  else {
    db_query("UPDATE {domain_theme} SET status = 1 WHERE domain_id = %d AND theme = '%s'", $domain_id, $theme);
  }
  // Return to the correct page.
  $form_state['redirect'] = 'admin/build/domain/theme/'. $domain_id;
  // Clear the cache.
  cache_clear_all();
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
function domain_theme_reset_form($form_state, $domain) {
  $extra['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form = confirm_form($extra, t('Are you sure you wish to reset the theme for %name?', array('%name' => $domain['sitename'])), 'admin/build/domain/theme/'. $domain['domain_id'], t('Submitting this form will restore default theme for this domain.'));
  return $form;
}

/**
 * FormsAPI for domain_theme_reset_form.
 */
function domain_theme_reset_form_submit($form, &$form_state) {
  db_query("DELETE FROM {domain_theme} WHERE domain_id = %d", $form_state['values']['domain_id']);
  drupal_set_message(t('Domain theme settings have been reset.'));
  $form_state['redirect'] = 'admin/build/domain/theme/'. $form_state['values']['domain_id'];
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
function theme_domain_theme_reset($domain) {
  $output = '';
  $output .= '<p>'. t('These settings will replace your default site theme when %name is the active domain.', array('%name' => $domain['sitename'])) .'</p>';
  $data = db_fetch_array(db_query("SELECT theme FROM {domain_theme} WHERE domain_id = %d", $domain['domain_id']));
  if (!empty($data)) {
    $output .= '<p>'. t('You may <a href="!url">erase these settings</a> to restore the default behavior.', array('!url' => url('admin/build/domain/theme-reset/'. $domain['domain_id']))) .'</p>';
  }
  return $output;
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
  $form_state['redirect'] = 'admin/build/domain/theme/'. $values['domain_id'];
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
