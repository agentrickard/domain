<?php

/**
 * @file
 * Hook implementations for this module.
 */

/**
 * Implements hook_user_login().
 */
function domain_config_hook_test_user_login($account) {
  \Drupal::state()->set('domain_config_test__user_login', TRUE);
}

/**
 * Implements hook_module_implements_alter().
 */
function domain_config_hook_test_module_implements_alter(&$implementations, $hook) {
  if ($hook == 'user_login') {
    // Turn off the domain_config_hook_test's hook_user_login (above).
    unset($implementations['domain_config_hook_test']);
  }
}
