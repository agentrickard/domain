// $Id$

/**
 * README
 *
 * This module makes variable overrides available for use by subdomains.
 * To activate this feature, you will need to directly call the data within settings.php.
 *
 * Look for this section of settings.php:
 *
 * Variable overrides:
 *
 * Load the 'settings' column from the {domain_conf} table and add the following to settings.php:
 *
 * $settings = {insert SQL statement here}
 * $conf = unserialize($settings);
 *
 * Doing so will load your saved domain settings over the site defaults.
 *
 * Code for doing so is in settings.inc for now.
 */