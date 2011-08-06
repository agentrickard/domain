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
 * Include bootstrap file and start bootstrap phases.
 */
include 'domain.bootstrap.inc';
domain_bootstrap();
