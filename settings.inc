<?php
/**
 *
 * @file
 * settings.inc
 *
 * This file should be included at the bottom of your settings.php file:
 * <?php
 * include '/sites/all/modules/domain/settings.inc';
 * ?>
 * If you have installed the domain module into a different folder than
 * /sites/all/modules/domain please adjust the path approriately.
 *
 * @ingroup domain
 */

/**
 * Include bootstrap file, setup checker function and start bootstrap phases.
 */
include 'domain.bootstrap.inc';
domain_settings_setup_ok();
domain_bootstrap();

/**
 * Small helper function to determine whether this file was included correctly in
 * the user's settings.php
 *
 * If it was included at the right time then cache.inc shouldn't be included
 * yet and the function 'cache_get' not be defined.
 *
 * When the function is called first (from within this file) the state is saved
 * in a static variable, every later call will return that boolean value.
 *
 * @return
 * TRUE if settings.inc was included correctly in settings.php, else FALSE
 */
function domain_settings_setup_ok() {
  static $state = NULL;
  if ($state === NULL) {
    $state = !function_exists('cache_get');
  }
  return $state;
}
