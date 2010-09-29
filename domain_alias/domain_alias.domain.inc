<?php
// $Id$

/**
 * @file
 * Domain hooks for Domain Alias.
 *
 * @ingroup domain_alias
 */

/**
 * Implements hook_domain_link()
 */
function domain_alias_domain_link($domain) {
  $links[] = array(
    'title' => t('aliases'),
    'path' => 'admin/structure/domain/alias/' . $domain['domain_id']
  );
  return $links;
}

/**
 * Implements hook_domain_nav().
 *
 * Ensure that active domains are correctly marked as active.
 */
function domain_alias_domain_nav($domain) {
  global $_domain;
  $extra = array();
  if (isset($_domain['active_alias_id'])) {
    $alias_id = $_domain['active_alias_id'];
    if (in_array($alias_id, array_keys($domain['aliases']))) {
      $extra['active'] = TRUE;
    }
  }
  return $extra;
}


/**
 * Implements hook_domain_load()
 *
 * Adds a list of all aliases for the current domain.
 */
function domain_alias_domain_load(&$domain) {
  // Get the domain aliases
  $domain['aliases'] = domain_alias_list($domain['domain_id']);
  if (isset($domain['active_alias_id'])) {
    $domain['aliases'][$domain['active_alias_id']]['active'] = TRUE;
  }
}

/**
 * Implements hook_domain_delete().
 *
 * When a {domain} entry is deleted also delete all related {domain_alias} entries.
 */
function domain_alias_domain_delete($domain, $form_values = array()) {
  db_delete('domain_alias')
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
}

/**
 * Implements hook_domain_view().
 */
function domain_alias_domain_view($op, $domain = array(), $query = NULL) {
  switch ($op) {
    case 'header':
      return array(array('data' => t('Aliases')));
      break;
    case 'data':
      if (empty($domain)) {
        return;
      }
      $aliases = domain_alias_list($domain['domain_id']);
      $pieces = array();
      foreach ($aliases as $alias) {
        if (strpos($alias['pattern'], '*') === FALSE) {
          $link = str_replace($domain['subdomain'], $domain['aliases'][$alias['alias_id']]['pattern'], domain_get_uri($domain));
          $pieces[] = l($alias['pattern'], $link, array('absolute' => TRUE));
        }
        else {
          $pieces[] = $alias['pattern'];
        }
      }
      $linktext = empty($pieces) ? 'add alias' : 'edit aliases';
      $action[] = l($linktext, 'admin/structure/domain/alias/' . $domain['domain_id']);
      $pieces = array_merge($action, $pieces);
      if (!empty($pieces)) {
        return array(theme('item_list', array('items' => $pieces)));
      }
      return array();
      break;
  }
}
