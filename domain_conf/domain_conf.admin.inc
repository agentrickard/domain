<?php

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
 *   The $domain object created by domain_lookup().
 *
 * @return
 *   The appropriate form or an error message.
 */
function domain_conf_page($domain) {
  if ($domain == -1) {
    return t('Invalid page requested.');
  }
  if ($domain['domain_id'] == domain_default_id()) {
    drupal_set_message(t('This is your default domain. Use of this form is discouraged. Set these values through the standard interface.'), 'warning', FALSE);
  }
  $output = theme('domain_conf_reset', array('domain' => $domain));
  drupal_set_title(t('Settings for !site', array('!site' => $domain['subdomain'])));
  $form = drupal_get_form('domain_conf_form', $domain);
  $form['intro'] = array(
    '#weight' => -5,
    '#markup' => $output,
  );
  return $form;
}

/**
 * Custom form to generate domain-specific site settings.
 *
 * The items on this form are taken from hook_domain_batch() and
 * hook_domain_conf(). See the API for more information.
 *
 * @param $domain
 *   The domain currently being updated.
 */
function domain_conf_form($form, &$form_state, $domain) {
  $form = array();
  $batch = domain_batch_actions();
  $settings = array();
  $data = db_query("SELECT settings FROM {domain_conf} WHERE domain_id = :domain_id", array(':domain_id' => $domain['domain_id']))->fetchField();
  if (!empty($data)) {
    $settings = domain_unserialize($data);
  }
  $default_group = t('Site configuration');
  foreach ($batch as $key => $action) {
    $permission = isset($action['#permission']) ? $action['#permission'] : 'administer domains';
    $collapsed = isset($action['#collapsed']) ? $action['#collapsed'] : FALSE;
    if (!user_access($permission) || $action['#domain_action'] != 'domain_conf') {
      continue;
    }
    if ($action['#form']['#type'] == 'select') {
      $action['#form']['#options'] = array('domain-conf-ignore' => t('Use primary domain settings')) + $action['#form']['#options'];
    }
    $group = isset($action['#group']) ? $action['#group'] : $default_group;
    if (!isset($form[$group])) {
      $form[$group] = array(
        '#type' => 'fieldset',
        '#title' => $group,
        '#collapsible' => TRUE,
        '#collapsed' => $collapsed,
      );
    }
    $form[$group][$key] = $action['#form'];
    $form[$group][$key]['#default_value'] = isset($settings[$key]) ? $settings[$key] : $action['#system_default'];
    // Change the path for the front page.
    if ($key == 'site_frontpage') {
      global $base_url;
      $prefix = $base_url . '/';
      $_path = parse_url($prefix);
      $str = $_path['host'];
      $fix = preg_replace("/$str/", $domain['subdomain'], $prefix, 1);
      $form[$default_group]['site_frontpage']['#field_prefix'] = $fix;
    }
  }
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  // Site name must be edited at the domain creation screen.
  if (variable_get('domain_sitename_override', 1) && isset($form[$default_group]['site_name'])) {
    $sitename = domain_conf_variable_get($domain['domain_id'], 'site_name');
    $form[$default_group]['site_name'] = array(
      '#disabled' => TRUE,
      '#title' => t('Site name'),
      '#description' => t('The name of this web site, as entered in the <a href="!url">domain-specific settings</a>.', array('!url' => url('admin/structure/domain/edit/' . $domain['domain_id']))),
      '#type' => 'textfield',
      '#default_value' => !empty($sitename) ? $sitename : $domain['sitename'],
    );
  }
  // Locale module is a little tricky, so handle it properly.
  $str = t('Language settings');
  if (isset($form[$str]['language_default']) && !isset($settings['language_default'])) {
    $form[$str]['language_default']['#default_value'] = NULL;
  }

  // Grab any extra elements defined by other modules.
  $extra = domain_conf_api(TRUE, $settings);
  // Merge the $extra and $form arrays.
  $form = array_merge_recursive($form, $extra);

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save domain settings'),
    '#weight' => 10
  );
  return $form;
}

/**
 * FormsAPI for domain_conf_form().
 */
function domain_conf_form_submit($form, &$form_state) {
  $new_settings = array();
  // Throw away what we don't need.
  $ignore = array('form_token', 'form_id', 'form_build_id', 'op', 'submit', 'domain_id');
  foreach ($form_state['values'] as $key => $value) {
    if (in_array($key, $ignore)) {
      continue;
    }
    $new_settings[$key] = $value;
  }
  // INSERT or UPDATE?
  $result = db_query("SELECT domain_id, settings FROM {domain_conf} WHERE domain_id = :domain_id", array(':domain_id' => $form_state['values']['domain_id']))->fetchObject();
  if (!empty($result->settings)) {
    $settings = domain_unserialize($result->settings);
    $merged_settings = array_merge($settings, $new_settings);
    db_update('domain_conf')
      ->fields(array(
          'settings' => serialize($merged_settings),
      ))
      ->condition('domain_id', $result->domain_id)
      ->execute();
  }
  else {
    db_insert('domain_conf')
      ->fields(array(
        'domain_id' => $form_state['values']['domain_id'],
        'settings' => serialize($new_settings)
      ))
      ->execute();
  }
  drupal_set_message(t('Domain options saved successfully.'));
  // Clear the cache.
  cache_clear_all();
}

/**
 * Resets configuration settings by removing the domain row from {domain_conf}.
 *
 * @param $domain
 *   The $domain object created by domain_lookup().
 *
 * @return
 *   A confirmation form.
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
 *   The $domain object for the selected domain.
 *
 * @return
 *   Themed HTML form.
 */

function domain_conf_reset_form($form, &$form_state, $domain) {
  $extra['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form = confirm_form($extra, t('Are you sure you wish to reset the settings for %name?', array('%name' => $domain['sitename'])), 'admin/structure/domain/view/' . $domain['domain_id'] . '/config', t('Submitting this form will restore default settings for this domain.'));
  return $form;
}

/**
 * FormsAPI for domain_conf_reset_form().
 */
function domain_conf_reset_form_submit($form, &$form_state) {
  db_delete('domain_conf')
    ->condition('domain_id', $form_state['values']['domain_id'])
    ->execute();
  drupal_set_message(t('Domain configuration settings have been reset.'));
  $form_state['redirect'] = 'admin/structure/domain/view/' . $form_state['values']['domain_id'] . '/config';
  // Clear the cache.
  cache_clear_all();
}

/**
 * Theme a message at the top of domain configuration pages.
 *
 * @param $domain
 *   The $domain object for the selected domain.
 *
 * @return
 *   Themed HTML messages.
 */
function theme_domain_conf_reset($variables) {
  $domain = $variables['domain'];
  $output = '';
  $output .= '<p>' . t('These settings will replace or supplement your default site settings when %name is the active domain.', array('%name' => $domain['sitename'])) . '</p>';
  $data = db_query("SELECT COUNT(domain_id) FROM {domain_conf} WHERE domain_id = :domain_id", array(':domain_id' => $domain['domain_id']))->fetchField();
  if (!empty($data)) {
    $output .= '<p>' . t('You may <a href="!url">erase these settings</a> to restore the default behavior.', array('!url' => url('admin/structure/domain/view/' . $domain['domain_id'] . '/conf-reset'))) . '</p>';
  }
  return $output;
}
