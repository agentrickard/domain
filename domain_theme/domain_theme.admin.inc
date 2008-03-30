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
    // Ensure we are on the proper domain.
    domain_goto($domain);

    // Load the system form file.
    include_once drupal_get_path('module', 'system') .'/system.admin.inc';

    drupal_set_title(t('!site : Domain theme settings', array('!site' => $domain['sitename'])));
    $output = theme_domain_theme_reset($domain);
    return $output . drupal_get_form('system_themes_form');
  }
  else {
    return t('Invalid domain request.');
  }
}

/**
 * Implements hook_form_alter()
 */
function domain_theme_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'system_themes_form') {
    global $_domain;
    // Get the current $theme for this domain, if available.
    $theme = domain_theme_lookup($_domain['domain_id']);
    if ($theme['theme']) {
      $form['theme_default']['#default_value'] = $theme['theme'];
    }
    // Unset options that are not allowed.
    $available = $form['status']['#options'];
    $allowed = $form['status']['#default_value'];
    foreach ($available as $key => $value) {
      if (!in_array($key, $allowed)) {
        // If the theme was disabled, then we have to use the default
        if ($key == $theme['theme']) {
          $form['theme_default']['#default_value'] = variable_get('theme_default', 'garland');
        }
        unset($form[$key]);
        unset($form['status']['#options'][$key]);
        unset($form['theme_default']['#options'][$key]);
      }
      else {
        $form['status']['#disabled'] = TRUE;
      }
    }
    // Use our own submit buttons.
    $unset = array('buttons', '#submit');
    foreach ($unset as $key) {
      unset($form[$key]);
    }
    // Message to users.
    $form['intro'] = array(
      '#value' => t('<p>Select the default theme for this domain. Theme-specific settings must be configured at <a href="!url">the system theme settings page</a>.</p>', array('!url' => url('admin/build/themes'))),
    );
    // Which domain are we editing?
    $form['domain_id'] = array(
      '#type' => 'value',
      '#value' => $_domain['domain_id'],
    );
    // Our submit handlers.
    $form['#submit'][] = 'domain_theme_submit';
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Set domain theme'),
    );
  }
}

/**
 * FormsAPI submut handler for the theme settings
 */
function domain_theme_submit($form, &$form_state) {
  $theme = $form_state['values']['theme_default'];
  $id = $form_state['values']['domain_id'];
  $settings = NULL; // This is a placeholder for advanced functions.
  $check = domain_theme_lookup($id);
  if ($check == -1) {
    db_query("INSERT INTO {domain_theme} (domain_id, theme, settings) VALUES (%d, '%s', %b)", $id, $theme, $settings);
  }
  else {
    db_query("UPDATE {domain_theme} SET theme = '%s', settings = %b WHERE domain_id = %d", $theme, $settings, $id);
  }
  // Return to the correct page.
  $form_state['redirect'] = 'admin/build/domain/theme/'. $id;
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
