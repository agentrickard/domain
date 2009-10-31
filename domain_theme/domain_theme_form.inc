<?php
// $Id$

/**
 * @file
 * Include file to handle theme configration screen.
 *
 * @ingroup domain_theme
 */

/**
 * Implement hook_form_alter()
 */
function domain_theme_form_alter($form_id, &$form) {
  if ($form_id == 'system_themes') {
    // The domain_goto() function assures that we are on the right domain.
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
    $form['#submit']['domain_theme_submit'] = array();
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Set domain theme'),
    );
  }
}

/**
 * FormsAPI submut handler for the theme settings
 */
function domain_theme_submit($form_id, $form_values) {
  $theme = $form_values['theme_default'];
  $id = $form_values['domain_id'];
  $settings = NULL; // This is a placeholder for advanced functions.
  $check = domain_theme_lookup($id);
  if ($check == -1) {
    db_query("INSERT INTO {domain_theme} VALUES (%d, '%s', %b)", $id, $theme, $settings);
  }
  else {
    db_query("UPDATE {domain_theme} SET theme = '%s', settings = %b WHERE domain_id = %d", $theme, $settings, $id);
  }
  // Clear the cache.
  cache_clear_all();
}
