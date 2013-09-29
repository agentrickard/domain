<?php

/**
 * @file
 * Support for domains in core Drupal objects
 */

/**
 * Migration class for importing domains into Drupal.
 *
 * In your migration class, you may map existing data directly to a Drupal
 * domain record.  The fields are as follows:
 *
 *  - subdomain -- the domain URL (e.g. example.com), not including a protocol
 *    or a path.
 *  - machine_name (optional) -- an optional machine name for the domain record.
 *  - sitename -- the human-readable name of the domain.
 *  - scheme (optional)-- the URL scheme for the domain, either http or https.
 *  - valid (optional) -- binary indicator that the domain is active or not.
 *  - weight (optional) -- signed integer indicating the sort order of the domain.
 *  - is_default (optional) -- binary indicator that this is the default domain.
 */
class MigrateDestinationDomain extends MigrateDestination {
  static public function getKeySchema() {
    return array(
      'machine_name' => array(
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
        'description' => 'Primary Key for domain entries.',
      ),
    );
  }

  public function __construct() {
    parent::__construct();
  }

  public function __toString() {
    $output = t('Domain');
    return $output;
  }

  /**
   * Returns a list of fields available to be mapped for domains.
   *
   * @param Migration $migration
   *  Optionally, the migration containing this destination.
   * @return array
   *  Keys: machine names of the fields (to be passed to addFieldMapping)
   *  Values: Human-friendly descriptions of the fields.
   */
  public function fields($migration = NULL) {
    $fields = array(
      'machine_name' => t('An optional machine name for the domain record. Will be generated is necessary.'),
      'subdomain' => t('The domain URL (e.g. example.com), not including a protocol or a path. Required.'),
      'sitename' => t('The human-readable name of the domain. Required.'),
      'scheme' => t('The URL scheme for the domain, either http or https. Optional.'),
      'valid' => t('Binary indicator that the domain is active or not. Optional.'),
      'weight' => t('Signed integer indicating the sort order of the domain. Optional.'),
      'is_default' => t('Binary indicator that this is the default domain. Optional.'),
    );
    return $fields;
  }

  /**
   * Import a single row.
   *
   * @param $domain
   *  Domain object to build. Prefilled with any fields mapped in the Migration.
   * @param $row
   *  Raw source data object - passed through to prepare/complete handlers.
   * @return array
   *  Array of key fields of the object that was saved if
   *  successful. FALSE on failure.
   */
  public function import(stdClass $domain, stdClass $row) {
    // Set up optional fields.
    if (empty($domain->machine_name)) {
      $domain->machine_name = domain_machine_name($domain->subdomain);
    }
    if (!isset($domain->valid)) {
      $domain->valid = 1;
    }
    if (empty($domain->scheme)) {
      $domain->scheme = 'http';
    }
    if (!isset($domain->weight)) {
      $domain->weight = count(domain_domains()) + 1;
    }
    if (!isset($domain->is_default)) {
      $domain->is_default = 0;
    }

    // Invoke migration prepare handlers.
    $this->prepare($domain, $row);

    // Domains are handled as arrays, so clone the object to an array.
    $domain = clone $domain;
    $domain = (array) $domain;

    // Check to see if this is a new domain.
    $update = FALSE;
    if ($data = domain_machine_name_load($domain['machine_name'])) {
      $update = TRUE;
    }

    // domain_save() provides no return callback, so we can't really test this
    // without running a domain_load() check.
    migrate_instrument_start('domain_save');
    domain_save($domain);
    migrate_instrument_stop('domain_save');

    // Return the new id or FALSE on failure.
    if ($data = domain_machine_name_load($domain['machine_name'])) {
      // Increment the count if the save succeeded.
      if ($update) {
        $this->numUpdated++;
      }
      else {
        $this->numCreated++;
      }
      // Return the primary key to the mapping table.
      $return = array($data['machine_name']);
    }
    else {
      $return = FALSE;
    }

    // Invoke migration complete handlers.
    $domain = (object) $data;
    $this->complete($domain, $row);

    return $return;
  }

  /**
   * Implementation of MigrateDestination::prepare().
   */
  public function prepare($domain, stdClass $row) {
    // We do nothing here but allow child classes to act.
    $migration = Migration::currentMigration();
    $domain->migrate = array(
      'machineName' => $migration->getMachineName(),
    );

    // Call any general handlers.
    migrate_handler_invoke_all('domain', 'prepare', $domain, $row);
    // Then call any prepare handler for this specific Migration.
    if (method_exists($migration, 'prepare')) {
      $migration->prepare($domain, $row);
    }
  }

  public function complete($domain, stdClass $row) {
    // We do nothing here but allow child classes to act.
    $migration = Migration::currentMigration();
    $domain->migrate = array(
      'machineName' => $migration->getMachineName(),
    );
    // Call any general handlers.
    migrate_handler_invoke_all('domain', 'complete', $domain, $row);
    // Then call any complete handler for this specific Migration.
    if (method_exists($migration, 'complete')) {
      $migration->complete($domain, $row);
    }
  }

  /**
   * Delete a single domain.
   *
   * @param $id
   *  Array of fields representing the key (in this case, just domain_name).
   */
  public function rollback(array $id) {
    $domain_name = reset($id);

    migrate_instrument_start('domain_delete');
    $this->prepareRollback($domain_name);
    if ($domain = domain_machine_name_load($domain_name)) {
      // We cannot delete the primary domain.
      $default = domain_default_id();
      if ($domain['domain_id'] != $default) {
        domain_delete($domain);
      }
      else {
        drupal_set_message(t('The default domain may not be rolled back.'), 'status', FALSE);
      }
    }
    $this->completeRollback($domain_name);
    migrate_instrument_stop('domain_delete');
  }

  /**
   * Give handlers a shot at cleaning up before a domain has been rolled back.
   *
   * @param $domain_name
   *  The machine_name ID of the domain about to be deleted.
   */
  public function prepareRollback($domain_name) {
    // We do nothing here but allow child classes to act.
    $migration = Migration::currentMigration();
    // Call any general handlers.
    migrate_handler_invoke_all('domain', 'prepareRollback', $domain_name);
    // Then call any complete handler for this specific Migration.
    if (method_exists($migration, 'prepareRollback')) {
      $migration->prepareRollback($domain_name);
    }
  }

  /**
   * Give handlers a shot at cleaning up after a domain has been rolled back.
   *
   * @param $domain_name
   *  ID of the domain which has been deleted.
   */
  public function completeRollback($domain_name) {
    // We do nothing here but allow child classes to act.
    $migration = Migration::currentMigration();
    // Call any general handlers.
    migrate_handler_invoke_all('domain', 'completeRollback', $domain_name);
    // Then call any complete handler for this specific Migration.
    if (method_exists($migration, 'completeRollback')) {
      $migration->completeRollback($domain_name);
    }
  }

}

/**
 * Migration class for importing Drupal nodes.
 *
 * In your migration class, there are two field mappings you may set.
 *
 *   - domain_site is a binary flag indicating that a node is assigned to
 *     "all affiliates" in your installation.
 *   - domains is a domain_id string or array of domain ids (either numeric or
 *     machine_name, and you may mix the two) that indicate the domain(s) the
 *     content should be published to.
 *
 * Note that failure to set these values explicitly will mean they are set
 * automatically. The domain_site value is configurable per content type. The
 * domains array will be populated with your default domain, if it exists.
 */
class DomainNodeHandler extends MigrateDestinationHandler {
  public function __construct() {
    $this->registerTypes(array('node'));
  }

  public function fields() {
    $fields = array(
      'domain_site' => t('Domain: all affiliates'),
      'domains' => t('Domain: assigned domains'),
    );
    return $fields;
  }

  public function prepare($node, stdClass $row) {
    // If set, ensure domains exist.
    $domains = array();
    if (isset($node->domains)) {
      $node->domains = (array) $node->domains;
      foreach ($node->domains as $id) {
        if ($domain = domain_load($id)) {
          $domains[$domain['domain_id']] = $domain['domain_id'];
        }
        if ($domain = domain_machine_name_load($id)) {
          $domains[$domain['domain_id']] = $domain['domain_id'];
        }
      }
    }
    // If empty, assign to the default domain. Note that domain_site can be
    // set automatically if not added by the migration.
    if (empty($domains) && $default = domain_default_id()) {
      $domains[$default] = $default;
    }
    $node->domains = $domains;
  }
}

/**
 * Migration class for supporting Drupal user accounts.
 *
 * In our migration class, you may add a domain_user field mapping. This field
 * may contain a domain_id string or array of domain ids (either numeric or
 * machine_name, and you may mix the two).
 *
 * If you fail to set domain_user, the user will be assigned to the default
 * domain, if it exists.
 */
class DomainUserHandler extends MigrateDestinationHandler {
  public function __construct() {
    $this->registerTypes(array('user'));
  }

  public function fields() {
    $fields = array(
      'domain_user' => t('Domain: assigned domains'),
    );
    return $fields;
  }

  public function prepare($user, stdClass $row) {
    // If set, ensure domains exist.
    $domains = array();
    domain_user_set($user);
    if (isset($user->domain_user)) {
      $user->domain_user = (array) $user->domain_user;
      foreach ($user->domain_user as $id) {
        if ($domain = domain_load($id)) {
          $domains[$domain['domain_id']] = $domain['domain_id'];
        }
        if ($domain = domain_machine_name_load($id)) {
          $domains[$domain['domain_id']] = $domain['domain_id'];
        }
      }
    }
    // If empty, assign to the default domain.
    if (empty($domains) && $default = domain_default_id()) {
      $domains[$default] = $default;
    }
    $user->domain_user = $domains;
  }
}
