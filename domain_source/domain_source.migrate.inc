<?php

/**
 * @file
 * Support for domain source in node migrations.
 */

/**
 * Implements hook_migrate_api().
 */
function domain_source_migrate_api() {
  return array(
    'api' => 2,
    'destination handlers' => array(
      'DomainSourceNodeHandler',
    ),
  );
}

class DomainSourceNodeHandler extends MigrateDestinationHandler {
  public function __construct() {
    $this->registerTypes(array('node'));
  }

  public function fields() {
    return array(
      'domain_source' => t('Domain: Domain Source'),
    );
  }
}
