<?php

/**
 * @file
 * Domain hooks for Domain Source.
 *
 * @ingroup domain_source
 */

/**
 * Implements hook_domain_source_alter().
 */
function domain_source_domain_source_alter(&$source, $nid) {
  $source = domain_source_lookup($nid);
}

/**
 * Implements hook_domain_delete().
 *
 * When deleting domain records, we remove all traces from the {domain_source}
 * table.
 */
function domain_source_domain_delete($domain, $form_values = array()) {
  db_delete('domain_source')
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
}

/**
 * Implements hook_domain_reassign().
 */
function domain_source_domain_reassign($old_domain, $new_domain, $table) {
  // On node changes, update {domain_source}.
  if ($table == 'domain_access') {
    db_update('domain_source')
      ->fields(array('domain_id' => $new_domain['domain_id']))
      ->condition('domain_id', $old_domain['domain_id'])
      ->execute();
  }
}
