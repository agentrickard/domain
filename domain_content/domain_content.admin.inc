<?php

/**
 * @file
 * Administration pages for Domain Content.
 *
 * @ingroup domain_content
 */

/**
 * The domain content page of menu callbacks.
 *
 * @return
 *  A link group for each domain the user can access.
 */
function domain_content_page() {
  $content = array();
  $content = system_admin_menu_block(menu_get_item('admin/domain/content'));
  // Print the list of options.
  if (!empty($content)) {
    $output = theme('admin_block_content', $content);
  }
  // There was an error or invalid request.
  else {
    $output = t('There are no valid domains configured.');
  }
  return $output;
}

/**
 * List the available domains for this user.
 *
 * See http://drupal.org/node/367752 for a discussion of the
 * need for this function.
 */
function domain_content_list() {
  global $user;
  if (user_access('administer nodes') || user_access('review content for all domains')) {
    // Return all domains.
    $query = "SELECT domain_id, subdomain, sitename, scheme FROM {domain} d";
  }
  else {
    if (empty($user->domain_user)) {
      return drupal_access_denied();
    }
    // Cast the -1 as 0.
    if (isset($user->domain_user[-1])) {
      unset($user->domain_user[-1]);
      $user->domain_user[0] = 0;
    }
    $query = "SELECT domain_id, subdomain, sitename, scheme FROM {domain} WHERE domain_id IN (". db_placeholders($user->domain_user, 'int') .")";
  }
  // Table information
  $header = array(
    array('data' => t('Id'), 'field' => 'domain_id'),
    array('data' => t('Site content'), 'field' => 'sitename'),
    array('data' => t('Content count')),
    array('data' => t('Site'), 'field' => 'subdomain'),
  );
  $query .= tablesort_sql($header);
  $result = pager_query($query, variable_get('domain_list_size', DOMAIN_LIST_SIZE), 0, NULL, $user->domain_user);
  $rows = array();
  while ($domain = db_fetch_array($result)) {
    $path = trim(domain_get_path($domain), '/');
    $rows[] = array(
      $domain['domain_id'],
      l(t('@sitename content', array('@sitename' => $domain['sitename'])), 'admin/domain/content/'. $domain['domain_id']),
      (int) db_result(db_query("SELECT COUNT(*) FROM {domain_access} WHERE gid = %d AND realm = 'domain_id'", $domain['domain_id'])),
      l(t('view site'), $path),
    );
  }

  $all = array(
    '-',
    l(t('Content assigned to all affiliates'), 'admin/domain/content/all'),
    (int) db_result(db_query("SELECT COUNT(*) FROM {domain_access} WHERE gid = 0 AND realm = 'domain_site'")),
    '',
  );
  array_unshift($rows, $all);
  if (!empty($rows)) {
    $output = '<p>'. t('The table below shows all the affiliates sites whose content you may edit. Click on the site name link to see all content assigned to that affiliate.') .'</p>';
    $output .= theme_table($header, $rows);
    $output .= theme('pager', NULL, variable_get('domain_list_size', DOMAIN_LIST_SIZE), 0);
    return $output;
  }
  else {
    return t('You do not have editing access to any domains. Please contact your site administrator.');
  }
}

/**
 * Content administration for a specific domain.
 * This callback puts the user on the current domain and then
 * fetches the appropirate content for batch editing.
 *
 * @param $domain_id
 *  The unique identifier for the currently active domain.
 * @param $all_affiliates
 *  A boolean flag that indicates whether to grant the domain_site node access
 *  realm for this content view.
 *
 * @return
 *  A link group for each domain the user can access.
 */
function domain_content_view($domain_id = NULL, $all_affiliates = FALSE) {
  global $_domain;

  // Load the active domain, which is not necessarily the current domain.
  if (!is_null($domain_id) && $domain_id != $_domain['domain_id']) {
    domain_set_domain($domain_id);
  }

  $output = '';
  // Override the $_domain global so we can see the appropriate content
  if (!is_null($domain_id)) {
    $_domain['site_grant'] = FALSE;
    drupal_set_title(t('Content for @domain', array('@domain' => $_domain['sitename'])));
  }
  else if ($all_affiliates) {
    $_domain['site_grant'] = TRUE;
    drupal_set_title(t('Content for all affiliate sites'));
  }

  // KILLSWITCH CASE: returns an error
  else {
    drupal_set_message(t('Invalid request'), 'error');
    $output .= t('<p>The specified domain does not exist.</p>');
    return $output;
  }
  $output .= domain_content_admin();
  return $output;
}

/**
 * Content admin page callback.
 *
 * @return
 *  A themed HTML batch content editing form.
 */
function domain_content_admin() {
  // Load the form basics for administering nodes.
  include_once drupal_get_path('module', 'node') .'/node.admin.inc';

  $output = drupal_get_form('node_filter_form');

  if (isset($_POST['nodes']) && $_POST['operation'] == 'delete') {
    return drupal_get_form('node_multiple_delete_confirm', $_POST['nodes']);
  }
  // Call the form first, to allow for the form_values array to be populated.
  $output .= drupal_get_form('domain_content_form');

  return $output;
}

/**
 * Rewrites node_admin_nodes() to use db_rewrite_sql().
 *
 * @return
 *  A form array according to the FormsAPI.
 */
function domain_content_form($form_state) {
  global $_domain;
  $filter = node_build_filter_query();

  // Bypass the superuser permissions by forcing an AND on {domain_access}.
  $filter['join'] .= " INNER JOIN {domain_access} dac ON dac.nid = n.nid ";
  $arg = arg(3);

  if ($arg != 'all') {
    // In this case, we must check the domain_id grant.
    // We use intval() here for security, since we are not filtering the query parameter otherwise.
    if (empty($filter['where'])) {
      $filter['where'] = " WHERE dac.realm = 'domain_id' AND dac.gid = ". intval($_domain['domain_id']) ." ";
    }
    else {
      $filter['where'] .= " AND dac.realm = 'domain_id' AND dac.gid = ". intval($_domain['domain_id']) ." ";
    }
  }
  else {
    // Or check the domain_site grant.
    if (empty($filter['where'])) {
      $filter['where'] = " WHERE dac.realm = 'domain_site' AND dac.gid = 0 ";
    }
    else {
      $filter['where'] .= " AND dac.realm = 'domain_site' AND dac.gid = 0 ";
    }
  }

  $result = pager_query(db_rewrite_sql('SELECT n.nid, n.changed FROM {node} n '. $filter['join'] . $filter['where'] .' ORDER BY n.changed DESC'), 50, 0, NULL, $filter['args']);

  // Reset the active domain.
  domain_reset_domain();

  $nodes = array();

  // Loop through the nodes to build the form
  while ($nid = db_fetch_object($result)) {
    $node = node_load($nid->nid);

    $form['title'][$node->nid] = array('#value' => l($node->title, 'node/'. $node->nid) .' '. theme('mark', node_mark($node->nid, $node->changed)));
    $form['name'][$node->nid] =  array('#value' => check_plain(node_get_types('name', $node)));
    $form['username'][$node->nid] = array('#value' => theme('username', $node));
    $form['status'][$node->nid] =  array('#value' =>  ($node->status ? t('published') : t('not published')));

    $node_domains = array();
    if (!empty($node->domains)) {
      foreach ($node->domains as $domain) {
        // The root domain is stored as -1, but cast as zero in the global variable.
        $key = ($domain == -1) ? 0 : $domain;
        // Convert the domain ids to data so we can print them.
        $node_domains[] = domain_lookup($key);
      }
    }
    // If we have multiple domains, print them.
    $items = array();
    if ($node->domain_site) {
      $items[-1] = t('All affiliates');
    }
    if (!empty($node_domains)) {
      foreach ($node_domains as $item) {
        $items[$item['domain_id']] = check_plain($item['sitename']);
      }
    }
    if (module_exists('domain_source')) {
      $source = NULL;
      $source = db_fetch_object(db_query("SELECT domain_id FROM {domain_source} WHERE nid = %d", $node->nid));
      if (!empty($source) && isset($items[$source->domain_id])) {
        $items[$source->domain_id] .= '*';
      }
    }
    $form['domains'][$node->nid] =  array('#value' => theme('item_list', $items));
    if (node_access('update', $node)) {
      $form['operations'][$node->nid] = array('#value' => l(t('edit'), 'node/'. $node->nid .'/edit', array('query' => drupal_get_destination())));
      $nodes[$node->nid] = '';
    }
  }
  // Privileged users can make global changes to Domain Access permissions.
  if (user_access('set domain access')) {
    domain_content_add_form_widget($form);
  }
  // Users must have passed at least one access check to have batch options.
  if ((user_access('administer nodes') || user_access('set domain access')) && !empty($nodes)) {
    $form['options'] = array('#type' => 'fieldset',
      '#title' => t('Update options'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#weight' => -1
    );
    $options = array();
    foreach (module_invoke_all('node_operations') as $operation => $array) {
      $options[$operation] = $array['label'];
    }
    $form['options']['operation'] = array('#type' => 'select', '#options' => $options,  '#default_value' => 'approve');
    $form['options']['submit'] = array('#type' => 'submit', '#value' => t('Update'));
    $form['nodes'] = array('#type' => 'checkboxes', '#options' => $nodes);
    // Filter the available operations based on user permissions.
    domain_content_filter_operations($form['options']['operation']['#options']);
  }
  $form['pager'] = array('#value' => theme('pager', NULL, 50, 0));
  $form['#theme'] = 'domain_content_admin_nodes';
  $form['#validate'][] = 'node_admin_nodes_validate';
  $form['#submit'][] = 'node_admin_nodes_submit';
  $form['#submit'][] = 'domain_content_process_nodes';
  return $form;
}

/**
 * Filters the node batch operations based on the user's permissions.
 *
 * @param &$operations
 * The complete list of operations, passed by reference.
 * @return
 * No return value. Modify by reference.
 */
function domain_content_filter_operations(&$operations) {
  $settings = variable_get('domain_form_elements', array('options', 'delete', 'comment_settings', 'path'));
  // Administer nodes can do anything.
  if (user_access('administer nodes')) {
    return;
  }
  // You must be able to edit to view this page, so check for the new delete perm.
  if (!user_access('delete domain nodes')) {
    unset($operations['delete']);
  }
  // The publish, promote and sticky operations all depend on having $settings['options'].
  if (!in_array('options', array_filter($settings))) {
    unset($operations['publish']);
    unset($operations['unpublish']);
    unset($operations['promote']);
    unset($operations['demote']);
    unset($operations['sticky']);
    unset($operations['unsticky']);
  }
}

/**
 * Replaces the default theme function for the node administration form.
 *
 * @param $form
 *  FormsAPI representation of the batch node edit form.
 * @return
 *  A themed HTML form.
 *
 * @ingroup themes
 */
function theme_domain_content_admin_nodes($form) {
  // Overview table:
  $header = array(theme('table_select_header_cell'), t('Title'), t('Affiliates'), t('Type'), t('Author'), t('Status'), t('Operations'));
  $output = '';
  $output .= drupal_render($form['domain']);
  $output .= drupal_render($form['options']);
  if (isset($form['title']) && is_array($form['title'])) {
    foreach (element_children($form['title']) as $key) {
      $row = array();
      $row[] = drupal_render($form['nodes'][$key]);
      $row[] = drupal_render($form['title'][$key]);
      $row[] = drupal_render($form['domains'][$key]);
      $row[] = drupal_render($form['name'][$key]);
      $row[] = drupal_render($form['username'][$key]);
      $row[] = drupal_render($form['status'][$key]);
      $row[] = drupal_render($form['operations'][$key]);
      $rows[] = $row;
    }

  }
  else  {
    $rows[] = array(array('data' => t('No posts available.'), 'colspan' => '6'));
  }

  $output .= theme('table', $header, $rows);
  if ($form['pager']['#value']) {
    $output .= drupal_render($form['pager']);
  }

  $output .= drupal_render($form);

  return $output;
}
