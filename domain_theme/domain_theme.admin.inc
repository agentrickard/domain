<?php

/**
 * @file
 * Include file to handle theme configuration screen
 *
 * @ingroup domain_theme
 */

/**
 * The domain theme page callback router.
 *
 * @param $domain
 *   The $domain object created by domain_lookup().
 */
function domain_theme_page($domain) {
  if (isset($domain['domain_id'])) {
    // Check the current domain.
    if ($domain['domain_id'] == domain_default_id()) {
      drupal_set_message(t('This is your default domain. Use of this form is discouraged. Set these values through the standard interface.'), 'warning', FALSE);
    }

    // Load the system form file.
    include_once drupal_get_path('module', 'system') . '/system.admin.inc';

    // Set the page title and generate the form.
    drupal_set_title(t('Theme for @site', array('@site' => $domain['subdomain'])));
    return drupal_get_form('domain_theme_form', $domain);
  }
  else {
    $build['content'] = array(
      '#markup' => t('Invalid domain request.'),
    );
    return $build;
  }
}

/**
 * Form callback to set theme per domain.
 *
 * @param $domain
 *   The $domain object created by domain_lookup().
 *
 * @return
 *   An HTML form.
 */
function domain_theme_form($form, &$form_state, $domain) {
  $form = array();
  // Get the current $theme for this domain, if available.
  $theme = domain_theme_lookup($domain['domain_id']);
  if ($theme['theme']) {
    $default = $theme['theme'];
  }
  else {
    $default = variable_get('theme_default', 'bartik');
    drupal_set_message(t('No theme has been set for this domain. It will use the default theme settings.'), 'status', FALSE);
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
  $themes = system_rebuild_theme_data();
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

/**
 * Form submit handler.
 */
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
  $form_state['redirect'] = 'admin/structure/domain/view/' . $domain_id . '/theme';
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
  $themes = system_rebuild_theme_data();
  $header = array(t('Screenshot'), t('Theme'), t('Default'), t('Options'));
  $rows = array();
  foreach (element_children($form['theme']) as $key) {
    $default = '';
    if ($form['theme'][$key]['#value'] == $key) {
      $default = t('(default theme)');
    }
    $row = array(
      theme('image', array('path' => $themes[$key]->info['screenshot'])),
      '<h3>' . $themes[$key]->info['name'] . ' ' . (isset($themes[$key]->info['version']) ? $themes[$key]->info['version'] : '') . ' ' . $default . '</h3>' . t($themes[$key]->info['description']),
      drupal_render($form['theme'][$key]),
      l(t('configure'), 'admin/structure/domain/view/' . $form['domain_id']['#value'] . '/theme/' . $key . '/theme-settings')
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
 *   The $domain object created by domain_lookup().
 *
 * @return
 *   A confirmation form.
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
 *   The $domain object for the selected domain.
 *
 * @return
 *   A themed HTML form.
 */
function domain_theme_reset_form($form, &$form_state, $domain) {
  $extra['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form = confirm_form($extra, t('Are you sure you wish to reset the theme for %name?', array('%name' => $domain['sitename'])), 'admin/structure/domain/view/' . $domain['domain_id'] . '/theme', t('Submitting this form will restore default theme for this domain.'));
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
  $form_state['redirect'] = 'admin/structure/domain/view/' . $form_state['values']['domain_id'] . '/theme';
  // Clear the cache.
  cache_clear_all();
}

/**
 * Theme a message at the top of domain theme pages.
 *
 * @param $domain
 *   The $domain object for the selected domain.
 *
 * @return
 *   Themed HTML messages.
 */
function theme_domain_theme_reset($variables) {
  $domain = $variables['domain'];
  $output = '';
  $output .= '<p>' . t('These settings will replace your default site theme when %name is the active domain.', array('%name' => $domain['sitename'])) . '</p>';
  $data = db_query("SELECT theme FROM {domain_theme} WHERE domain_id = :domain_id", array(':domain_id' => $domain['domain_id']))->fetchAssoc();
  if (!empty($data)) {
    $output .= '<p>' . t('You may <a href="!url">erase these settings</a> to restore the default behavior.', array('!url' => url('admin/structure/domain/view/' . $domain['domain_id'] . '/theme-reset'))) . '</p>';
  }
  return $output;
}

/**
 * The domain theme page callback router.
 *
 * @param $theme
 *   The theme being configured.
 * @param $domain
 *   The $domain object created by domain_lookup().
 */
function domain_theme_settings($domain, $theme) {
  // Load the system form file.
  include_once(drupal_get_path('module', 'system') . '/system.admin.inc');

  // Set the proper context for the user.
  $settings = db_query("SELECT theme, settings FROM {domain_theme} WHERE domain_id = :domain_id AND theme = :theme", array(':domain_id' => $domain['domain_id'], ':theme' => $theme))->fetchAssoc();
  drupal_set_message(t('You are viewing the %theme settings for %domain.', array('%theme' => $theme, '%domain' => $domain['subdomain'])), 'status', FALSE);
  drupal_set_title(t('@theme settings for @site', array('@site' => $domain['subdomain'], '@theme' => $theme)));

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
  // Prepare a filepath for color module settings.
  $domain = domain_lookup($values['domain_id']);
  // We aren't using $filepath in Drupal 7; this can probably be removed.
  $filepath = file_default_scheme() . ':/' . '/domain-' . $domain['domain_id'];
  $vars = array('palette', 'stylesheets', 'logo', 'files', 'screenshot');
  foreach ($vars as $variable) {
    $preset = variable_get('color_' . $values['theme'] . '_' . $variable, '');
    if (!empty($preset)) {
      $values['color_' . $values['theme'] . '_' . $variable] = $preset;
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
  if (!empty($values['color_' . $values['theme'] . '_stylesheets'][0])) {
    $filepath = domain_theme_get_color_path($values['color_' . $values['theme'] . '_stylesheets'][0]);
  }
  // Deal with file uploads.
  domain_theme_file_upload($values);
  // Set the variables for saving.
  $key = $values['var'];
  $domain_id = $values['domain_id'];
  $theme = $values['theme'];
  // Exclude unnecessary elements before saving.
  unset($values['var'], $values['submit'], $values['reset'], $values['form_id'], $values['op'], $values['form_build_id'], $values['form_token'], $values['domain_id'], $values['domain_color'], $values['domain_color_defaults']);
  $settings = serialize($values);
  // Insert or Update?
  // This lookup returns -1 on failure.
  $check = domain_theme_lookup($domain_id, $theme);
  // Update.
  if ($check != -1) {
    db_update('domain_theme')
      ->fields(array(
        'settings' => $settings,
        'filepath' => $filepath,
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
        'settings' => $settings,
        'status' => 0,
        'filepath' => $filepath,
      ))
      ->execute();
  }
  // If nothing is active, then we make this one active.
  $active = db_query("SELECT COUNT(domain_id) FROM {domain_theme} WHERE domain_id = :domain_id AND status = 1", array(':domain_id' => $domain_id))->fetchField();
  if (empty($active)) {
    db_update('domain_theme')
      ->fields(array(
        'status' => 1,
      ))
      ->condition('domain_id', $domain_id)
      ->condition('theme', $theme)
      ->execute();
    drupal_set_message(t('%theme has been set as the default theme for %domain', array('%theme' => $theme, '%domain' => $domain['sitename'])));
  }
  // Clear the cache.
  cache_clear_all();
  // Finish processing the form.
  drupal_set_message(t('The configuration options have been saved.'));
  $form_state['redirect'] = 'admin/structure/domain/view/' . $domain_id . '/theme';
}

/**
 * Custom submit handler.
 *
 * If the theme is saved as 'default', we have to be sure not to delete
 * existing color directories. This section is copied from color.module.
 *
 * @see color_form_submit()
 */
function domain_theme_color_submit($form, &$form_state) {
  $theme = $form_state['values']['theme'];
  $info = $form_state['values']['info'];

  // Resolve palette.
  $palette = $form_state['values']['palette'];
  if ($form_state['values']['scheme'] != '') {
    foreach ($palette as $key => $color) {
      if (isset($info['schemes'][$form_state['values']['scheme']]['colors'][$key])) {
        $palette[$key] = $info['schemes'][$form_state['values']['scheme']]['colors'][$key];
      }
    }
    $palette += $info['schemes']['default']['colors'];
  }
  if (implode(',', color_get_palette($theme, TRUE)) == implode(',', $palette)) {
    $form_state['values']['palette']['domain_default'] = 'xyz';
  }
}

/**
 * Helper function for handling logo uploads.
 *
 * @see system_theme_settings_submit()
 *
 * We have disabled the normal function because we do not
 * want our form results saved to the {variables} table.
 *
 * @param $values
 *   An array of form values, passed by reference.
 */
function domain_theme_file_upload(&$values) {
  // If the user uploaded a new logo or favicon, save it to a permanent location
  // and use it in place of the default theme-provided file.
  if ($file = $values['logo_upload']) {
    unset($values['logo_upload']);
    $filename = file_unmanaged_copy($file->uri);
    $values['default_logo'] = 0;
    $values['logo_path'] = $filename;
    $values['toggle_logo'] = 1;
  }
  if ($file = $values['favicon_upload']) {
    unset($values['favicon_upload']);
    $filename = file_unmanaged_copy($file->uri);
    $values['default_favicon'] = 0;
    $values['favicon_path'] = $filename;
    $values['toggle_favicon'] = 1;
  }

  // If the user entered a path relative to the system files directory for
  // a logo or favicon, store a public:// URI so the theme system can handle it.
  if (!empty($values['logo_path'])) {
    $values['logo_path'] = _system_theme_settings_validate_path($values['logo_path']);
  }
  if (!empty($values['favicon_path'])) {
    $values['favicon_path'] = _system_theme_settings_validate_path($values['favicon_path']);
  }

  if (empty($values['default_favicon']) && !empty($values['favicon_path'])) {
    $values['favicon_mimetype'] = file_get_mimetype($values['favicon_path']);
  }
}
