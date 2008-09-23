<?php
// $Id$

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
 *  The $domain object created by domain_lookup().
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
 * The current form state, passed by FormsAPI.
 * @param $domain
 * An array containing the record from the {domain} table.
 * @param $arguments
 * An array of additional hidden key/value pairs to pass to the form.
 * Used by child modules to control behaviors.
 */
function domain_alias_form($form_state, $domain, $arguments = array()) {
  $form = array();

  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $record_edit_url = 'admin/build/domain/'. ($domain['domain_id'] == 0 ? '' : 'edit/'. $domain['domain_id']);
  $form['domain'] = array(
    '#type' => 'item',
    '#title' => t('Main domain name for %title', array('%title' => $domain['sitename'])),
    '#value' => $domain['subdomain'],
    '#description' => t('You can change the main domain name on <a href="!url">this page</a>.', array('!url' => url($record_edit_url))),
    );

  $form['domain_alias'] = array(
    '#type' => 'fieldset',
    '#title' => t('Edit domain aliases for @title', array('@title' => $domain['sitename'])),
    '#collapsible' => TRUE,
    '#tree' => TRUE,
    '#description' => t('Defined aliases for this domain record, using the full
      <em>path.example.com</em> format.  Can only contain lower-case alphanumeric characters.
      Leave off the http:// and the trailing slash.'),
  );

  // List all existing aliases
  if (isset($domain['aliases']) && is_array($domain['aliases'])) {
    foreach ($domain['aliases'] as $alias_id => $alias) {
      $form['domain_alias'][$alias_id] = array(
        '#type' => 'fieldset',
        '#title' => t('<strong>Alias (#'. $alias_id .')</strong>'),
        '#tree' => TRUE,
      );
      $form['domain_alias'][$alias_id]['pattern'] = array(
        '#type' => 'textfield',
        '#default_value' => $alias['pattern'],
        '#maxlength' => 80,
      );
      $form['domain_alias'][$alias_id]['redirect'] = array(
        '#type' => 'checkbox',
        '#default_value' => $alias['redirect'],
        '#title' => 'Redirect to main domain',
      );
    }
  }

  // 1st fieldset for a new alias
  $form['domain_alias']['new1'] = array(
    '#type' => 'fieldset',
    '#title' => t('<strong>New Alias</strong>'),
    '#tree' => TRUE,
  );
  $form['domain_alias']['new1']['pattern'] = array(
    '#type' => 'textfield',
    '#description' => t('<p><em>Advanced:</em> You can specify a pattern for your domains by
      using * (asterisk) to match any number of random characters and ? (questionmark) to match exactly one random character.
      Example: <strong>*.example.com</strong> would match any subdomain of <em>example.com</em> onto this domain record.</p>'),
    '#maxlength' => 80,
  );
  $form['domain_alias']['new1']['redirect'] = array(
    '#type' => 'checkbox',
    '#default_value' => 0,
    '#title' => 'Redirect to main domain',
  );

  // 2nd fieldset for a new alias
  $form['domain_alias']['new2'] = array(
    '#type' => 'fieldset',
    '#title' => t('<strong>New Alias</strong>'),
    '#tree' => TRUE,
  );
  $form['domain_alias']['new2']['pattern'] = array(
    '#type' => 'textfield',
    '#maxlength' => 80,
  );
  $form['domain_alias']['new2']['redirect'] = array(
    '#type' => 'checkbox',
    '#default_value' => 0,
    '#title' => 'Redirect to main domain',
  );

  // 3rd fieldset for a new alias
  $form['domain_alias']['new3'] = array(
    '#type' => 'fieldset',
    '#title' => t('<strong>New Alias</strong>'),
    '#tree' => TRUE,
  );
  $form['domain_alias']['new3']['pattern'] = array(
    '#type' => 'textfield',
    '#maxlength' => 80,
  );
  $form['domain_alias']['new3']['redirect'] = array(
    '#type' => 'checkbox',
    '#default_value' => 0,
    '#title' => 'Redirect to main domain',
  );

  $form['domain_alias']['info_morefields'] = array(
    '#prefix' => '<div class="info_morefields">',
    '#suffix' => '</div>',
    '#value' =>  t('<p>If you need more fields just save the record, come back to this form and you will find three new empty fields. </p>'),
  );

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save aliases'));
  return $form;
}

/**
 * FAPI for domain_alias_form()
 *
 * @todo
 * The part of the validation that makes sure that no two patterns map to the
 * same domainname is rather messy. Needs cleaning up, a rewrite or at least
 * some comments.
 */
function domain_alias_form_validate($form, &$form_state) {
  // validate aliases
  $aliases = array();
  foreach ($form_state['values']['domain_alias'] as $id => $alias) {
    if (!empty($alias['pattern'])) {
      $alias = $alias['pattern'];
      // 1) check that the same alias is not entered twice
      if (in_array($alias, $aliases)) {
        form_error($form['domain_alias'][$id]['pattern'], t('%name is already defined. Please use unique values.', array('%name' => $alias)));
      }

      // 2) check that the alias doesn't contain any invalid characters
      $c = preg_match('/^[a-z0-9\.\+\-\*\?]*$/', $alias);
      if ($c == 0) {
        form_error($form['domain_alias'][$id]['pattern'], t('%name contains invalid characters. ', array('%name' => $alias)));
      }

      // 3) check that the alias doesn't match another alias defined for this domain
      $r = Array('.' => '\.', '+' => '\+', '-' => '\-', '*' => '[a-z0-9\.\+\-]*', '?' => '[a-z0-9\.\+\-]?');
      $_pattern = str_replace(array_keys($r), $r, $alias);
      foreach ($aliases as $_alias) {
        $_alias_pattern = str_replace(array_keys($r), $r, $_alias);
        if (preg_match('@'. $_pattern .'@i', $_alias) || preg_match('@'. $_alias_pattern .'@i', $alias)) {
          form_error($form['domain_alias'][$id]['pattern'], t('%name matches %name2. Please use unambiguous values. ', array('%name' => $alias, '%name2' => $_alias)));
          break;
        }
      }

      // 4) check that the alias or a pattern matching the same domainname isn't already used by a different entry
      $_pattern = _domain_alias_placeholders_tosql($alias);

      $_domain_id = isset($form_state['values']['domain_id']) ? $form_state['values']['domain_id'] : NULL;
      if (!is_null($_domain_id)) {
        $_alias = db_fetch_array(db_query_range("SELECT alias_id, domain_id, pattern FROM {domain_alias} WHERE domain_id <> %d AND (pattern LIKE ('%s') OR '%s' LIKE (pattern))", $_domain_id, $_pattern, $alias, 0, 1));
      }
      else {
        $_alias = db_fetch_array(db_query_range("SELECT alias_id, domain_id, pattern FROM {domain_alias} WHERE pattern LIKE ('%s') OR '%s' LIKE (pattern)", $_pattern, $alias, 0, 1));
      }

      if (!empty($_alias)) {
        form_error($form['domain_alias'][$id]['pattern'],
          t('%name matches <a href="!url" title="Edit Aliases for domain !id">Alias #!aid</a> (%name2). Please use unambiguous values. ',
            array('%name' => $alias, '%name2' => _domain_alias_placeholders_fromsql($_alias['pattern']),
            '!url' => url('admin/build/domain/alias/'. $_alias['domain_id']), '!id' => $_alias['domain_id'], '!aid' => $_alias['alias_id'])));
      }

      $aliases[] = $alias;
    }
  }
}

/**
 * FAPI for domain_alias_form().
 */
function domain_alias_form_submit($form, &$form_state) {
  foreach ($form_state['values']['domain_alias'] as $id => $alias) {
    $alias['pattern'] = _domain_alias_placeholders_tosql($alias['pattern']);
    // if not a new alias ..
    if (strpos($id, 'new') === FALSE) {
      if (empty($alias['pattern'])) {
        // .. and alias empty -> delete
        db_query("DELETE FROM {domain_alias} WHERE alias_id = %d", $id);
      }
      else {
        // .. and alias not empty -> update
        db_query("UPDATE {domain_alias} SET pattern = '%s', redirect = '%d' WHERE alias_id = %d", $alias['pattern'], intval($alias['redirect']), $id);
      }
    }
    // if this is a new alias and it's not blank -> create new one
    else if (!empty($alias['pattern'])) {
      db_query("INSERT INTO {domain_alias} (domain_id, pattern, redirect) VALUES ('%d', '%s', '%d')", $form_state['values']['domain_id'], $alias['pattern'], intval($alias['redirect']));
    }
  }

  drupal_set_message(t('Domain aliases updated.'));
  $form_state['redirect'] = 'admin/build/domain/view';
}