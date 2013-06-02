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
  $source_id = domain_source_lookup($nid);
  // If FALSE returned, no source is defined.
  if (!$source_id) {
    return;
  }
  // DOMAIN_SOURCE_USE_ACTIVE is the status for 'Use active domain.'
  if ($source_id == DOMAIN_SOURCE_USE_ACTIVE) {
    $source = domain_get_domain();
  }
  // The source_id always returns a valid domain.
  else {
    $source = domain_lookup($source_id);
  }
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
