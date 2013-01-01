<?php

/**
 * @file
 * Domain hook tests.
 */

/**
 * Implements hook_domain_load().
 *
 * Add a 'test' variable to the domain array.
 */
function domain_test_domain_load(&$domain) {
  // Add a variable to the $domain array.
  $domain['testvar'] = TRUE;
}

/**
 * Implements hook_domain_insert().
 *
 * Change the sitename value to 'foobar'.
 */
function domain_test_domain_insert($domain, $form_values = array()) {
  if ($domain['sitename'] == 'testfoo') {
    db_update('domain')
      ->fields(array('sitename' => 'foobar'))
      ->condition('domain_id', $domain['domain_id'])
      ->execute();
  }
}

/**
 * Implements hook_domain_update().
 *
 * Change the sitename value to 'testfoo'.
 */
function domain_test_domain_update($domain, $form_values = array()) {
  if ($domain['sitename'] == 'foobar') {
    db_update('domain')
      ->fields(array('sitename' => 'testfoo'))
      ->condition('domain_id', $domain['domain_id'])
      ->execute();
  }
}

/**
 * Implements hook_domain_delete().
 *
 * On deletion, set the test static to 'deleted'.
 */
function domain_test_domain_delete($domain) {
  domain_test_set('delete');
}

/**
 * Implements hook_domain_cron().
 *
 * We should both receive a $domain array and be setting the active
 * domain to the current $domain, so we can test both here.
 */
function domain_test_domain_cron($domain) {
  $_domain = domain_get_domain();
  if ($_domain['domain_id'] == $domain['domain_id']) {
    domain_test_set($domain['sitename']);
  }
  else {
    domain_test_set('Failed');
  }
}

/**
 * Implements hook_domain_validate_alter().
 *
 * Remove any errors on validation.
 */
function domain_test_domain_validate_alter(&$errors, $subdomain) {
  if ($subdomain == 'thisshouldfail') {
    $errors = array();
  }
}

/**
 * Implements hook_domain_reassign().
 */
function domain_test_domain_reassign($old_domain, $new_domain, $table) {
  domain_test_set($table);
}

/**
 * Implements hook_domain_batch().
 */
function domain_test_domain_batch() {
  // A simple function to rename my setting in Domain Configuration.
  $batch = array();
  $batch['domain_test'] = array(
    '#form' => array(
      '#title' => t('My Settings'),
      '#type' => 'textfield',
      '#size' => 40,
      '#maxlength' => 80,
      '#description' => t('A description for the form'),
      '#required' => TRUE,
    ),
    '#permission' => 'administer domains',
    '#domain_action' => 'domain_conf',
    '#meta_description' => t('Edit my setting value.'),
    '#variable' => 'domain_mysetting',
    '#validate' => 'domain_mysetting_validate',
    '#data_type' => 'string',
    '#weight' => 0,
    '#group' => t('My settings'),
    '#collapsed' => FALSE,
    '#update_all' => TRUE,
    '#module' => t('Domain Access'),
  );
  return $batch;
}

/**
 * Implements hook_domain_batch_alter().
 */
function domain_test_domain_batch_alter(&$batch) {
  // Rename 'Put site into maintenance mode'.
  $batch['maintenance_mode']['#form']['#title'] = t('Test reset value');
}

/**
 * Implements hook_domain_invalid_request().
 */
function domain_test_domain_invalid_request($path, $_domain, $account) {
  if ($path == 'admin') {
    return TRUE;
  }
  return FALSE;
}
