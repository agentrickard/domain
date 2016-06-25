<?php
/**
 *
 * @file
 * settings.inc
 *
 * This file should be included at the bottom of your settings.php file:
 *
 * include DRUPAL_ROOT . '/sites/all/modules/domain/settings.inc';
 *
 * If you have installed the domain module into a different folder than
 * /sites/all/modules/domain please adjust the path appropriately.
 *
 * @ingroup domain
 */

/**
 * Include bootstrap file and start bootstrap phases.
 */
include 'domain.bootstrap.inc';

// If the module is not installed properly, or Drupal not yet installed, we
// can explode rather maginificently. Using a try/catch, we can at least fail
// silently.
try {
  domain_bootstrap();
}
catch(Exception $e) {
  // No error logging is available yet, assume we are installing Drupal.
  // See https://www.drupal.org/node/1559486.
}
