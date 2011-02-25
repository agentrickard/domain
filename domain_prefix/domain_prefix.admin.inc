<?php

/**
 * @file
 * Admin page functions for selective table prefixing for use with Domain Access.
 *
 * @ingroup domain_prefix
 */

/**
 * Get the tables with the active prefix
 *
 * @param $prefix
 *  The table prefix used with this domain. Optional.
 */
function domain_prefix_get_tables($prefix = NULL) {
  // Check for default prefix settings.
  if (empty($prefix)) {
    $prefix = domain_prefix_get_prefix();
  }

  // Get the database schema information.
  $schema = drupal_get_schema();
  ksort($schema);

  $tables = array();
  $disallow = domain_prefix_disallow();
  foreach ($schema as $table => $data) {
    if (!in_array($table, $disallow)) {
      $tables[$table]['tablename'] = $table;
      $tables[$table]['module'] = $data['module'];
    }
  }
  // Sort them by module
  uasort($tables, '_domain_prefix_sort');
  return $tables;
}

/**
 * Helper sort function
 */
function _domain_prefix_sort($a, $b) {
  $_a = str_replace('_', '', $a['tablename']);
  $_b = str_replace('_', '', $b['tablename']);
  if ($a['module'] == $b['module']) {
    return strcmp($_a, $_b);
  }
  return strcmp($a['module'], $b['module']);
}

/**
 * FormsAPI for generating the configuration form
 */
function domain_prefix_configure_form() {
  // We must use the settings from the root domain.
  $default = domain_default();
  domain_set_domain($default['domain_id'], TRUE);

  // Get the tables for the root installation.
  $tables = domain_prefix_get_tables();

  // Remove the disallowed tables.
  $disallow = domain_prefix_disallow();
  // Get the current settings.
  $info = variable_get('domain_prefix', NULL);
  $settings = $info['settings'];
  $source_defaults = $info['sources'];

  $form = array();
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain creation rules'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE
  );
  $form['domain']['domain_prefix_options'] = array(
    '#type' => 'radios',
    '#title' => t('Domain creation options'),
    '#description' => t('Determines what actions to take when creating new domain records.'),
    '#options' => array(1 => t('Generate tables as defined below'), 0 => t('Do not generate any tables')),
    '#default_value' => variable_get('domain_prefix_options', 1),
    '#required' => TRUE
  );
  $last = ''; // Flag for module grouping.
  // Get the source table data.
  $root = domain_default();
  foreach ($tables as $table => $info) {
    if (!in_array($table, $disallow) && substr($table, 0, 7) != 'domain_') {
      if (empty($settings[$table])) {
        $settings[$table] = DOMAIN_PREFIX_IGNORE;
        $source_defaults['_source_'. $table] = 0;
      }
      $module = domain_prefix_get_name($info);
      if ($last != $module) {
        $last = $module;
      }
      else {
        $module = '';
      }
      $options = array();
      $options[DOMAIN_PREFIX_IGNORE] = t('ignore');
      $options[DOMAIN_PREFIX_CREATE] = t('create');
      $options[DOMAIN_PREFIX_COPY] = t('copy');
      $form['domain_prefix'][$table] = array(
        '#type' => 'radios',
        '#title' => $table,
        '#options' => $options,
        '#default_value' => $settings[$table],
        '#description' => $module
      );
      // Get the table copying options for this entry.
      // Can't pass a zero through FormAPI select.
      $sources = array();
      $sources[0] = $root['sitename'];
      // Check to see if other table prefixes have been created.
      $result = db_query("SELECT dp.domain_id, d.sitename FROM {domain_prefix} dp
        INNER JOIN {domain} d ON dp.domain_id = d.domain_id
        WHERE dp.tablename = '%s' AND dp.status > %d", $table, 1);
      while ($data = db_fetch_array($result)) {
        $sources[$data['domain_id']] = $data['sitename'];
      }

      $form['domain_source']['_source_'. $table] = array(
        '#type' => 'select',
        '#title' => '',
        '#options' => $sources,
        '#default_value' => $source_defaults['_source_'. $table],
      );
    }
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save prefix settings'),
  );
  $form['restore'] = array(
    '#type' => 'submit',
    '#value' => t('Restore defaults'),
  );
  // Reset the active domain.
  domain_reset_domain(TRUE);
  return $form;
}

/**
 * FormsAPI for the domain_prefix_configure_form.
 */
function domain_prefix_configure_form_submit($form, &$form_state) {
  // Throw away what we don't need.
  $unset = array('op', 'submit', 'restore', 'form_token', 'form_id', 'form_build_id', 'domain_prefix_options');
  $data = $form_state['values'];
  foreach ($unset as $key) {
    unset($data[$key]);
  }
  if ($form_state['values']['op'] == $form_state['values']['restore']) {
    variable_del('domain_prefix');
    drupal_set_message(t('Default prefix settings reset.'));
  }
  else {
    // Process the source data.
    foreach ($data as $key => $value) {
      if (substr($key, 0, 8) == '_source_') {
        $info['sources'][$key] = $value;
      }
      else {
        $info['settings'][$key] = $value;
      }
    }
    variable_set('domain_prefix', $info);
    drupal_set_message(t('Default prefix settings changed.'));
  }
  variable_set('domain_prefix_options', $form_state['values']['domain_prefix_options']);
}


/**
 * FormsAPI theming for domain_prefix_configure_form.
 */
function theme_domain_prefix_configure_form($form) {
  $output = t('<p>These settings control advanced functions.  Please read the documentation carefully.</p>');
  $header = array(t('Table'), t('Source'), t('Ignore'), t('Create'), t('Copy'));
  if (!empty($form['prefix_theme']['#value'])) {
    $header[] = t('Update');
    $header[] = t('Drop');
    unset($form['prefix_theme']);
  }
  $output = drupal_render($form['domain']);
  foreach (element_children($form['domain_prefix']) as $key) {
    if ($form['domain_prefix'][$key]['#description']) {
      $rows[] = array('<b>'. $form['domain_prefix'][$key]['#description'] .'</b>', array('', 'colspan' => count($header)));
    }
    $row = array();
    $row[] = ' - '. $form['domain_prefix'][$key]['#title'];
    // Insert the source selection element.
    $row[] = drupal_render($form['domain_source']['_source_'. $key]);
    foreach (element_children($form['domain_prefix'][$key]) as $option) {
      $row[] = drupal_render($form['domain_prefix'][$key][$option]);
    }
    // Throw the rest away, since we already rendered what we want from it.
    $render = drupal_render($form['domain_prefix'][$key]);
    $rows[] = $row;
  }
  $output .= theme('table', $header, $rows);
  $output .= drupal_render($form);
  return $output;
}

/**
 * The table prefixing page for a domain.
 *
 * @param $domain
 *  The domain array provided by domain_lookup().
 * @param $arguments
 *  An array of additional hidden key/value pairs to pass to the form.
 *  Used by child modules to control behaviors.
 */
function domain_prefix_form($form_state, $domain, $arguments = array()) {
  // If an invalid request was made, reject it.
  if ($domain == -1) {
    return drupal_access_denied();
  }
  // We must use the settings from the root domain.
  $default = domain_default();
  domain_set_domain($default['domain_id'], TRUE);

  drupal_set_title(t('Table prefixing for !domain', array('!domain' => check_plain($domain['sitename']))));
  // Remove the disallowed tables.
  $disallow = domain_prefix_disallow();

  // Get the default table set and settings.
  $default = domain_prefix_get_tables();
  // Get the current settings.
  $info = variable_get('domain_prefix', NULL);
  $settings = $info['settings'];
  $source_defaults = $info['sources'];

  // Check the defaults against those saved for this domain.
  $result = db_query("SELECT tablename, source FROM {domain_prefix} WHERE domain_id = %d", $domain['domain_id']);
  while ($sourcedata = db_fetch_array($result)) {
    $source_defaults['_source_'. $sourcedata['tablename']] = $sourcedata['source'];
  }
  // Get the root source table data.
  $root = domain_default();
  if (empty($settings) && empty($_POST)) {
    drupal_set_message(t('There are no default settings configured.'));
  }

  // Get the stored table data for this domain.
  $tables = domain_prefix_lookup($domain['domain_id']);

  $submit = t('Update domain tables');
  if (empty($tables)) {
    if (empty($_POST) &&  empty($form_state['values']['execute']) && empty($arguments['user_submitted'])) {
      drupal_set_message(t('The table creation sequence has not run for this domain.'));
    }
    $submit = t('Generate domain tables');
    $table_options = $default;
  }
  else {
    $table_options = array();
    $settings = array();
    // Process the existing tables.
    foreach ($tables as $name => $table) {
      if (array_key_exists($name, $default)) {
        $table_options[$table['tablename']] = $table;
        $settings[$table['tablename']] = $table['status'];
      }
    }
    // Check to see if new tables have been added.
    foreach ($default as $name => $table) {
      if (!array_key_exists($name, $table_options)) {
        $table_options[$name] = $table;
      }
    }
  }

  // Sort the options by module.
  uasort($table_options, '_domain_prefix_sort');
  // All tables are prefixed as 'domain_#_'
  $prefix = domain_prefix_string($domain['domain_id']);

  // Generate the form.
  $form = array();

  // The $arguments arrray allows other modules to pass values to change the bahavior
  // of submit and validate functions.
  if (!empty($arguments)) {
    $form['domain_arguments'] = array('#type' => 'value', '#value' => $arguments);
  }
  $delete_flag = 0; // Flag for the theme function delete column
  $last = ''; // Flag for module groupings.
  foreach ($table_options as $table => $info) {
    if (!in_array($table, $disallow)) {
      if (empty($settings[$table])) {
        $settings[$table] = DOMAIN_PREFIX_IGNORE;
      }
      $options = array();
      $options[DOMAIN_PREFIX_IGNORE] = t('ignore');
      $options[DOMAIN_PREFIX_CREATE] = t('create');
      $options[DOMAIN_PREFIX_COPY] = t('copy');
      if ($settings[$table] > 0) {
        $exists = domain_prefix_table_exists($prefix, $table);
        if ($exists > 0) {
          $options[DOMAIN_PREFIX_UPDATE] = t('update');
          $options[DOMAIN_PREFIX_DROP] = t('drop');
          $delete_flag++;
        }
      }
      $module = domain_prefix_get_name($info);
      if ($last != $module) {
        $last = $module;
      }
      else {
        $module = '';
      }
      $form['domain_prefix'][$table] = array(
        '#type' => 'radios',
        '#title' => $table,
        '#options' => $options,
        '#default_value' => $settings[$table],
        '#description' => $module
      );
      // Get the table copying options for this entry.
      // Can't pass a zero through FormAPI select.
      $sources = array();
      $sources[0] = $root['sitename'];
      // Check to see if other table prefixes have been created.
      $result = db_query("SELECT dp.domain_id, d.sitename FROM {domain_prefix} dp
        INNER JOIN {domain} d ON dp.domain_id = d.domain_id
        WHERE dp.tablename = '%s' AND dp.status > %d", $table, 1);
      while ($data = db_fetch_array($result)) {
        // Cannot copy onto itself.
        if ($data['domain_id'] != $domain['domain_id']) {
          $sources[$data['domain_id']] = $data['sitename'];
        }
      }

      $form['domain_source']['_source_'. $table] = array(
        '#type' => 'select',
        '#title' => '',
        '#options' => $sources,
        '#default_value' => isset($source_defaults['_source_'. $table]) ? $source_defaults['_source_'. $table] : DOMAIN_PREFIX_IGNORE,
      );
    }
  }
  $form['#theme'] = 'domain_prefix_configure_form';
  $form['table_data'] = array('#type' => 'value', '#value' => $default);
  $form['prefix_theme'] = array('#type' => 'value', '#value' => $delete_flag);
  $form['domain_id'] = array('#type' => 'value', '#value' => $domain['domain_id']);
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => $submit
  );
  // Reset the active domain.
  domain_reset_domain(TRUE);
  return $form;
}

/**
 * FormsAPI for domain_prefix_form.
 */
function domain_prefix_form_submit($form, &$form_state) {
  // Flag messages for the administrative user only.
  $msg = TRUE;
  $create = TRUE;
  if (!empty($form_state['values']['domain_arguments']['user_submitted'])) {
    $msg = FALSE;
    // Should we create tables for user domains?
    $create = variable_get('domain_user_prefixing', 0);
  }
  if (!empty($create)) {
    // Throw away what we don't need.
    $prefix = domain_prefix_string($form_state['values']['domain_id']);
    $tables = $form_state['values']['table_data'];
    $unset = array('prefix_theme', 'domain_id', 'op', 'submit', 'restore', 'form_token', 'form_id', 'form_build_id', 'execute', 'table_data');
    $data = $form_state['values'];
    foreach ($unset as $key) {
      unset($data[$key]);
    }
    // Delete existing records, but get the existing values first.
    $current = domain_prefix_lookup($form_state['values']['domain_id']);
    db_query("DELETE FROM {domain_prefix} WHERE domain_id = %d", $form_state['values']['domain_id']);

    foreach ($data as $key => $value) {
      // Do not process tables for the source elements.
      // But be sure to set the proper source table prefix for copying data.
      if (substr($key, 0, 8) != '_source_') {
        $source = (isset($data['_source_'. $key])) ? $data['_source_'. $key] : 0;
        if ($source > 0) {
          $source_prefix = domain_prefix_string($source);
        }
        else {
          $source_prefix = '';
        }

        $update = FALSE;
        if (empty($value)) {
          $value = DOMAIN_PREFIX_IGNORE;
          $update = TRUE;
        }

        $newtable = db_escape_table($prefix . $key);
        $module = $tables[$key]['module'];
        $exists = domain_prefix_table_exists($prefix, $key);
        $oldtable = db_escape_table($key);
        $sourcetable = db_escape_table($source_prefix . $key);
        $table_schema = drupal_get_schema($key);
        if ($value == DOMAIN_PREFIX_CREATE) {
          if (!$exists) {
            // TODO: Make this a nice update function with a progress bar.
            $data_table = db_create_table_sql($newtable, $table_schema);
            db_query($data_table[0]);
            if ($msg) {
              drupal_set_message(t('!string table created.', array('!string' => $newtable)));
            }
            $update = TRUE;
          }
          else if ($current[$oldtable]['status'] == DOMAIN_PREFIX_COPY) {
            drupal_set_message(t('!string table cannot be created, since it already exists.', array('!string' => $newtable)));
          }
        }
        else if ($value == DOMAIN_PREFIX_COPY) {
          if (!$exists) {
            // TODO: Make this a nice update function with a progress bar.
            $data_table = db_create_table_sql($newtable, $table_schema);
            db_query($data_table[0]);
            domain_prefix_insert_data($table_schema, $newtable, $sourcetable);
            if ($msg) {
              drupal_set_message(t('!string table copied.', array('!string' => $newtable)));
            }
            $update = TRUE;
          }
          else if ($current[$oldtable]['status'] == DOMAIN_PREFIX_CREATE) {
            drupal_set_message(t('!string table cannot be copied, since it already exists.', array('!string' => $newtable)));
          }
        }
        else if ($value == DOMAIN_PREFIX_UPDATE) {
          if ($exists > 0) {
            db_query("TRUNCATE TABLE {%s}", $newtable);
            domain_prefix_insert_data($table_schema, $newtable, $sourcetable);
            if ($msg) {
              drupal_set_message(t('!string table updated from source.', array('!string' => $newtable)));
            }
            $update = TRUE;
            // Set the stored value to "copy" for record keeping.
            $value = DOMAIN_PREFIX_COPY;
          }
        }
        else if ($value == DOMAIN_PREFIX_DROP) {
          if ($exists > 0) {
            db_query("DROP TABLE {%s}", $newtable);
            $value = DOMAIN_PREFIX_IGNORE;
            if ($msg) {
              drupal_set_message(t('!string table dropped.', array('!string' => $newtable)));
            }
            $update = TRUE;
          }
          else {
            drupal_set_message(t('!string table does not exist.', array('!string' => $newtable)));
          }
        }
        // Update our records.
        if (!$update && $value != 1 && isset($current[$oldtable]['status'])) {
          $value = $current[$oldtable]['status'];
        }
        db_query("INSERT INTO {domain_prefix} (domain_id, status, tablename, module, source) VALUES (%d, %d, '%s', '%s', %d)", $form_state['values']['domain_id'], $value, $key, $module, $form_state['values']['_source_'. $key]);
        // Prevent errors after the form is passed.
        $form_state['values'][$key] = $value;
      }
    }
  }
  // Clear the cache.
  cache_clear_all();
}

/**
 * Insert data from one table into another.
 *
 * We need a function here to prevent accidental errors when
 * copying or updating serial fields that have a 0 element, like {users}.
 *
 * @param $schema
 *   The table definition provided by hook_schema.
 * @param $newtable
 *   The name of the table being created or updated.
 * @param $sourcetable
 *   The name of the source data table.
 */
function domain_prefix_insert_data($schema, $newtable, $sourcetable) {
  $zero_row = FALSE;
  // Check the source table for serial fields that include a zero row.
  foreach ($schema['fields'] as $field => $info) {
    if ($info['type'] == 'serial') {
      $zero_row = db_fetch_array(db_query("SELECT * FROM {%s} WHERE %s = 0", $sourcetable, $field));
    }
    break;
  }

  // If no zero row, then we are done.
  if (empty($zero_row)) {
    // Insert the records.
    db_query("INSERT INTO {%s} SELECT * FROM {%s}", $newtable, $sourcetable);
    return;
  }
  // Run the query, but treat row zero with care.
  db_query("INSERT INTO {%s} SELECT * FROM {%s} WHERE %s > 0", $newtable, $sourcetable, $field);
  db_query("INSERT INTO {%s} SELECT * FROM {%s} WHERE %s = 0", $newtable, $sourcetable, $field);
  $id = db_last_insert_id($newtable, $field);
  db_query("UPDATE {%s} SET %s = 0 WHERE %s = %d", $newtable, $field, $field, $id);
  // On MySQL at least, we can safely modify the autoincrement sequence.
  if ($GLOBALS['db_type'] == 'mysqli') {
    db_query("ALTER TABLE %s AUTO_INCREMENT = %d", $newtable, $id);
  }
}

/**
 * Lookup stored table information for a domain.
 *
 * @param $domain_id
 *  The domain_id taken from {domain}.
 */
function domain_prefix_lookup($domain_id, $clear = FALSE) {
  static $domain_prefix;
  if (!empty($clear) || !isset($domain_prefix[$domain_id])) {
    $domain_prefix[$domain_id] = array();
    $result = db_query("SELECT domain_id, status, tablename, module, source FROM {domain_prefix} WHERE domain_id = %d", $domain_id);
    while ($data = db_fetch_array($result)) {
      $domain_prefix[$domain_id][$data['tablename']] = $data;
    }
  }
  return $domain_prefix[$domain_id];
}

/**
 * Return the human-readable name of a module.
 *
 * @param $module
 *  Module information proivided by the SchemaAPI.
 * @return
 * A human-readable string identifying the module.
 */
function domain_prefix_get_name($module) {
  static $modules = array();
  $key = $module['module'];
  if (!isset($modules[$key])) {
    // Get the module data to match to the schema.
    $modules[$key] = drupal_parse_info_file(drupal_get_path('module', $key) .'/'. $key .'.info');
  }
  return $modules[$key]['name'];
}

/**
 * Names of tables explicitly not allowed to be copied
 */
function domain_prefix_disallow() {
  return array(
    'domain',
    'domain_access',
    'domain_alias',
    'domain_conf',
    'domain_editor',
    'domain_prefix',
    'domain_source',
    'domain_theme',
    'domain_user'
  );
}

/**
 * Drop tables created by this module.
 *
 * @param $domain_id
 *  The domain_id taken from {domain}.
 */
function domain_prefix_drop_records($domain_id) {
  $result = db_query("SELECT tablename FROM {domain_prefix} WHERE domain_id = %d AND status > %d", $domain_id, 1);
  $prefix = domain_prefix_string($domain_id);
  while ($tables = db_fetch_array($result)) {
    $table = db_escape_table($prefix . $tables['tablename']);
    $exists = domain_prefix_table_exists($prefix, $tables['tablename']);
    if ($exists > 0) {
      db_query("DROP TABLE {%s}", $table);
      drupal_set_message(t('!string table dropped.', array('!string' => $table)));
    }
  }
  db_query("DELETE FROM {domain_prefix} WHERE domain_id = %d", $domain_id);
}
