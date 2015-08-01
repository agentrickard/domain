<?php

/**
 * @file
 * Administration functions for the domain_alias module.
 *
 * @ingroup domain_alias
 */

/**
 * Edit aliases
 *
 * @param $domain
 *    The $domain object created by domain_lookup().
 */
function domain_alias($domain) {
  if (!is_array($domain)) {
    return t('Invalid page requested.');
  }
  return drupal_get_form('domain_alias_form', $domain);
}

/**
 * FAPI for editing domain aliases
 *
 * @param $form_state
 *   The current form state, passed by FormsAPI.
 * @param $domain
 *   An array containing the record from the {domain} table.
 * @param $arguments
 *   An array of additional hidden key/value pairs to pass to the form.
 *   Used by child modules to control behaviors.
 */
function domain_alias_form($form, &$form_state, $domain, $arguments = array()) {
  $form = array();
  drupal_set_title(t('Aliases for !domain', array('!domain' => $domain['subdomain'])));
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $record_edit_url = 'admin/build/domain/' . ($domain['domain_id'] == 0 ? '' : 'edit/' . $domain['domain_id']);
  $form['domain_help'] = array(
    '#type' => 'markup',
    '#markup' => domain_alias_help_text(),
  );
  $form['domain'] = array(
    '#type' => 'markup',
    '#markup' => t('Registered aliases for <a href="!url"%title</a>', array('!url' => url($record_edit_url), '%title' => $domain['subdomain'])),
  );
  $form['domain_alias'] = array(
    '#tree' => TRUE,
  );
  // List all existing aliases
  if (isset($domain['aliases']) && is_array($domain['aliases'])) {
    foreach ($domain['aliases'] as $alias_id => $alias) {
      $form['domain_alias'][$alias_id] = array(
        '#tree' => TRUE,
      );
      $form['domain_alias'][$alias_id]['alias_id'] = array(
        '#type' => 'value',
        '#value' => $alias_id,
      );
      $form['domain_alias'][$alias_id]['redirect'] = array(
        '#type' => 'select',
        '#options' => array(
          0 => t('Do not redirect'),
          301 => t('Permanent redirect (301)'),
          302 => t('Temporary redirect (302)'),
        ),
        '#default_value' => $alias['redirect'],
      );
      $form['domain_alias'][$alias_id]['pattern'] = array(
        '#type' => 'textfield',
        '#default_value' => $alias['pattern'],
        '#maxlength' => 255,
        '#width' => 40,
      );
      $form['domain_alias'][$alias_id]['delete'] = array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      );
    }
  }
  $form['domain_new'] = array(
    '#type' => 'markup',
    '#markup' => t('Add new aliases'),
  );
  $form['domain_new_help'] = array(
    '#type' => 'markup',
    '#markup' => t('To create a new alias, enter the matching pattern. Check the <em>redirect</em> box if you would like requests made to the alias to redirect to the registered domain.
      <em>You may enter up to five (5) aliases at a time.</em>'),
  );
  $form['domain_alias_new'] = array(
    '#tree' => TRUE,
  );
  for ($i = 0; $i < 5; $i++) {
    $form['domain_alias_new'][$i]['redirect'] = array(
      '#type' => 'select',
      '#options' => array(
        0 => t('Do not redirect'),
        301 => t('Permanent redirect (301)'),
        302 => t('Temporary redirect (302)'),
      ),
      '#default_value' => 0,
    );
    $form['domain_alias_new'][$i]['pattern'] = array(
      '#type' => 'textfield',
      '#default_value' => NULL,
      '#maxlength' => 255,
      '#width' => 40,
    );
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save aliases'));
  return $form;
}

/**
 * FAPI for domain_alias_form()
 */
function domain_alias_form_validate($form, &$form_state) {
  // Validate aliases
  $aliases = array();
  // Validate updates -- this array might not have data.
  if (isset($form_state['values']['domain_alias'])) {
    foreach ($form_state['values']['domain_alias'] as $count => $alias) {
      $validate = TRUE;
      // Delete requests and unchanged aliases do not need the validation step.
      $original_alias = domain_alias_lookup(NULL, $count);
      if ($original_alias['pattern'] == $alias['pattern']) {
        // In this case, no change and no error set, unless no changes made.
        $validate = FALSE;
      }
      if ($original_alias['redirect'] != $alias['redirect']) {
        // In this case, we updated the redirect settings only.
        $validate = FALSE;
        $aliases[] = 'update placeholder';
      }
      if ($form_state['values']['domain_alias'][$count]['delete']) {
        // Set a value so we do not return an error on empty array.
        $aliases[] = 'delete placeholder';
        $validate = FALSE;
      }
      if ($validate) {
        // Run the validation routine.
        $aliases[] = _domain_alias_validate($form, $alias, $count, $aliases, 'domain_alias');
      }
    }
  }
  // Validate new domain aliases -- this array should always have data.
  foreach ($form_state['values']['domain_alias_new'] as $count => $alias) {
    if (empty($alias['pattern'])) {
      continue;
    }
    $aliases[] = _domain_alias_validate($form, $alias, $count, $aliases, 'domain_alias_new');
  }
  if (empty($aliases)) {
    form_error($form['domain'], t('No changes were made.'));
  }
}

/**
 * Helper function to validate alias entries.
 *
 * @param $alias
 *   The form element defining the alias.
 * @param $id
 *   The form array id, either a number or an alias_id.
 * @param $aliases
 *   The array of currently processed alias strings.
 * @param $type
 *   The type of alias, used to determine form errors.
 *
 * @return
 *   A valid alias string, or an error.
 */
function _domain_alias_validate($form, $alias, $id, $aliases, $type) {
  $alias = $alias['pattern'];
  // 1) Check that the same alias is not entered twice.
  if (in_array($alias, $aliases)) {
    form_error($form[$type][$id]['pattern'], t('%name is already defined. You must use unique values.', array('%name' => $alias)));
    return;
  }
  // 2) Check that the alias only has one wildcard.
  $count = substr_count($alias, '*') + substr_count($alias, '?');
  if ($count > 1) {
    form_error($form[$type][$id]['pattern'], t('You may only have one wildcard character in each alias.'));
    return;
  }
  // 3) Only one colon allowed, and it must be followed by numbers only.
  $count = substr_count($alias, ':');
  if ($count > 1) {
    form_error($form[$type][$id]['pattern'], t('You may only have one colon ":" character in each alias.'));
    return;
  }
  elseif ($count == 1) {
    $int = substr($alias, strpos($alias, ':') + 1);
    if (!is_numeric($int)) {
      form_error($form[$type][$id]['pattern'], t('A colon may only be followed by an integer indicating the proper port.'));
      return;
    }
  }
  // 4) Check that the alias doesn't contain any invalid characters.
  $check = preg_match('/^[a-z0-9\.\+\-\*\?:]*$/', $alias);
  if ($check == 0) {
    form_error($form[$type][$id]['pattern'], t('%name contains invalid characters.', array('%name' => $alias)));
  }
  // 5) Check that the alias is not a direct match for a registered domain.
  $check = preg_match('/[a-z0-9\.\+\-:]*$/', $alias);
  if ($check == 1) {
    $test = db_query("SELECT COUNT(domain_id) FROM {domain} WHERE subdomain = :alias", array(':alias' => $alias))->fetchField();
    if ($test > 0) {
      form_error($form[$type][$id]['pattern'], t('%name matches an existing domain record.', array('%name' => $alias)));
    }
  }
  // 6) Check that the alias or a pattern matching the same domain name does not exist.
  $_pattern = _domain_alias_placeholders_to_sql($alias);
  if (isset($form_state['values']['domain_id']) && $_pattern != $form_state['values']['domain_id']) {
    $_alias = db_query("SELECT alias_id, domain_id, pattern FROM {domain_alias} WHERE pattern = :pattern1 OR pattern  = :pattern2", array(':pattern1' => $_pattern, ':pattern2' => $form_state['values']['domain_id']))->fetchAssoc();
  }
  else {
    $_alias = db_query("SELECT alias_id, domain_id, pattern FROM {domain_alias} WHERE pattern = :pattern", array(':pattern' => $_pattern))->fetchAssoc();
  }
  if (!empty($_alias)) {
    form_error($form[$type][$id]['pattern'],
      t('%name matches <a href="!url" title="Edit aliases for domain !id">alias #!aid</a> (%existing). You must use unique values. ',
        array(
          '%name' => $alias,
          '%existing' => _domain_alias_placeholders_from_sql($_alias['pattern']),
          '!url' => url('admin/build/domain/alias/' . $_alias['domain_id']),
          '!id' => $_alias['domain_id'],
          '!aid' => $_alias['alias_id']
        )
      )
    );
  }
  return $alias;
}

/**
 * Forms_API for domain_alias_form().
 */
function domain_alias_form_submit($form, &$form_state) {
  // The new alias fields are always present.
  foreach ($form_state['values']['domain_alias_new'] as $id => $alias) {
    if (!empty($alias['pattern'])) {
      $alias['pattern'] = _domain_alias_placeholders_to_sql($alias['pattern']);
      db_insert('domain_alias')
        ->fields(array(
            'domain_id' => $form_state['values']['domain_id'],
            'pattern' => $alias['pattern'],
            'redirect' => intval($alias['redirect']),
          ))
        ->execute();
    }
  }
  if (isset($form_state['values']['domain_alias'])) {
    foreach ($form_state['values']['domain_alias'] as $id => $alias) {
      $alias['pattern'] = _domain_alias_placeholders_to_sql($alias['pattern']);
      if ($alias['delete']) {
        db_delete('domain_alias')
          ->condition('alias_id', $id)
          ->execute();
      }
      else {
        db_update('domain_alias')
          ->condition('alias_id', $id)
          ->fields(array(
              'pattern' => $alias['pattern'],
              'redirect' => intval($alias['redirect']),
            ))
          ->execute();
      }
    }
  }
  drupal_set_message(t('Domain aliases updated successfully.'));
  // Clear the cache.
  cache_clear_all();
}

/**
 * Form theming.
 */
function theme_domain_alias_form($variables) {
  $form = $variables['form'];
  $output = '';

  $redirect = t('Check the redirect box to send requests for an alias to the registered domain.');
  $output .= drupal_render($form['domain_help']);
  $output .= '<br /><h3>' . drupal_render($form['domain']) . '</h3>';
  // Edit existing records.
  $elements = element_children($form['domain_alias']);
  if (!empty($elements)) {
    $header = array(t('Id'), t('Redirect'), t('Pattern'), t('Delete'));
    $rows = array();
    foreach ($elements as $element) {
      $rows[] = array(
        $form['domain_alias'][$element]['alias_id']['#value'],
        drupal_render($form['domain_alias'][$element]['redirect']),
        drupal_render($form['domain_alias'][$element]['pattern']),
        drupal_render($form['domain_alias'][$element]['delete']),
      );
    }
    $output .= theme('table', array('header' => $header, 'rows' => $rows));
    $output .= '<p><em>' . $redirect . '</em></p>';
  }
  else {
    $output .= '<p>' . t('There are no aliases recorded for this domain.') . '</p>';
  }
  // Add new records.
  $output .= '<br /><h3>' . drupal_render($form['domain_new']) . '</h3>';
  $output .= '<p>' . drupal_render($form['domain_new_help']) . '</p>';
  $header = array(t('Redirect'), t('Pattern'));
  $rows = array();
  foreach (element_children($form['domain_alias_new']) as $element) {
    $rows[] = array(
      drupal_render($form['domain_alias_new'][$element]['redirect']),
      drupal_render($form['domain_alias_new'][$element]['pattern']),
    );
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= '<p><em>' . $redirect . '</em></p>';
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Help text for the form.
 */
function domain_alias_help_text() {
  $output = t('<p>A domain alias is used to register multiple valid domain names to a single record within Domain Access.
    You may enter as many unique aliases per domain as you wish. </p>
    <p>You may specify a pattern for your domains by using <strong>*</strong> (asterisk) to match any number of random
    characters and <strong>?</strong> (question mark) to match exactly one random character.
    For example: <em>*.example.com</em> would match any HTTP request made to a subdomain of <em>example.com</em>
    to the domain record for <em>example.com</em>. NOTE: <em>Only one wildcard is allowed per alias.</em></p>');
  return $output;
}
