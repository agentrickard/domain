<?php

/**
 * @file
 * Block view functions for Domain Access.
 */

/**
 * A nifty little domain-switcher block, useful during debugging.
 *
 * @see domain_block_view()
 */
function domain_block_view_switcher() {
  $output = '';
  $list = array();
  $domains = domain_domains();
  $msg = FALSE;
  foreach ($domains as $domain) {
    if ($domain['valid']) {
      $title = $domain['sitename'];
      $allow = TRUE;
    }
    else {
      $title = $domain['sitename'] . ' *';
      $allow = FALSE;
      if (user_access('access inactive domains')) {
        $msg = TRUE;
        $allow = TRUE;
      }
    }
    if ($allow) {
      $list[] = l($title, domain_get_uri($domain), array('absolute' => TRUE));
    }
  }
  $items = array('items' => $list);
  $output = theme('item_list', $items);
  $block = array(
    'subject' => t('Domain switcher'),
    'content' => array(
      '#markup' => $output,
    ),
  );
  return $block;
}

/**
 * Prints information about the current node.
 *
 * @see domain_block_view()
 */
function domain_block_view_information() {
  $output = '';
  $node = menu_get_object();
  if (empty($node->nid)) {
    return;
  }
  // Print the assigned domains.
  if (!empty($node->subdomains)) {
    $output .= theme('item_list', array('items' => $node->subdomains, 'title' => t('Assigned domains')));
  }
  // Print the link source domain.
  $this_domain = domain_get_node_match($node->nid);
  $output .= theme('item_list', array('items' => array(check_plain($this_domain['sitename'])), 'title' => t('Source domain')));
  if (empty($output)) {
    $output = t('This node is not assigned to a domain.');
  }
  else {
    $output = '<p>' . t('%node is published with the following Domain Access rules:', array('%node' => $node->title)) . '</p>' . $output;
  }

  $block = array(
    'subject' => t('Domain access information'),
    'content' => array(
      '#markup' => $output,
    ),
  );
  return $block;
}

/**
 * Prints information about the current HTTP request.
 *
 * @see domain_block_view()
 */
function domain_block_view_server() {
  $_domain = domain_get_domain();
  $output = '';
  $header = array(t('Property'), t('Value'));
  $rows = array();
  $rows[] = array(
    t('HTTP_HOST request'),
    check_plain($_SERVER['HTTP_HOST']),
  );
  $check = domain_lookup(NULL, $_SERVER['HTTP_HOST']);
  $match = t('TRUE');
  if ($check == -1) {
    // Specific check for Domain Alias.
    if (isset($_domain['active_alias_id'])) {
      $match = t('ALIAS: Using alias %id', array('%id' => $_domain['active_alias_id']));
    }
    else {
      $match = t('FALSE: Using default domain.');
    }
  }
  $rows[] = array(
    t('Domain match'),
    $match,
  );
  foreach ($_domain as $key => $value) {
    if (is_null($value)) {
      $value = t('NULL');
    }
    elseif ($value === TRUE) {
      $value = t('TRUE');
    }
    elseif ($value === FALSE) {
      $value = t('FALSE');
    }
    $rows[] = array(
      check_plain($key),
      !is_array($value) ? check_plain($value) : _domain_block_print_array($value),
    );
  }
  $output = theme('table', array('header' => $header, 'rows' => $rows));
  $block = array(
    'subject' => t('Domain server information'),
    'content' => array(
      '#markup' => $output,
    ),
  );
  return $block;
}

/**
 * Prints array data for the server block.
 *
 * @param $array
 *  An array of data. Note that we support two levels of nesting.
 *
 * @return
 *  A suitable output string.
 */
function _domain_block_print_array($array) {
  $items = array();
  foreach ($array as $key => $val) {
    $value = 'array';
    if (!is_array($val)) {
      $value = check_plain($val);
    }
    else {
      $list = array();
      foreach ($val as $k => $v) {
        $list[] = t('@key : @value', array('@key' => $k, '@value' => $v));
      }
      $value = implode('<br />', $list);
    }
    $items[] = t('@key : !value', array('@key' => $key, '!value' => $value));
  }
  return theme('item_list', array('items' => $items));
}
