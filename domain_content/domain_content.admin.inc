<?php
// $Id$

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
      l(t('!sitename content', array('!sitename' => $domain['sitename'])), 'admin/domain/content/'. $domain['domain_id']),
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
    drupal_set_title(t('Content for !domain', array('!domain' => $_domain['sitename'])));
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

  $result = pager_query(db_rewrite_sql('SELECT n.nid FROM {node} n '. $filter['join'] . $filter['where'] .' ORDER BY n.changed DESC'), 50, 0, NULL, $filter['args']);

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
        $items[$item['domain_id']] = $item['sitename'];
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
    $options = array();
    $format = domain_select_format();
    foreach (domain_domains() as $data) {
      // Cannot pass zero in checkboxes.
      ($data['domain_id'] == 0) ? $key = -1 : $key = $data['domain_id'];
      // The domain must be valid.
      if ($data['valid'] || user_access('access inactive domains')) {
        // Filter checkboxes but not select lists.
        $options[$key] = empty($format) ? check_plain($data['sitename']) : $data['sitename'];
      }
    }
    $form['domain'] = array(
      '#type' => 'fieldset',
      '#title' => t('Affiliate publishing options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#prefix' => '<div class="description">'. t('If you select <em>Change affiliate publishing options</em> above, you should confirm the <em>Affiliate publishing options</em> settings below.') .'</div>'
    );
    $form['domain']['behavior'] = array(
      '#type' => 'radios',
      '#title' => t('Update behavior'),
      '#options' => array(0 => t('Replace old values with new settings'), 1 => t('Add new settings to existing values'), 2 => t('Remove selected domains from existing values')),
      '#description' => t('Defines how new grants will be applied to the updated nodes.'),
      '#default_value' => 0,
    );
    $form['domain']['domain_site'] = array(
      '#type' => 'checkbox',
      '#prefix' => t('<p><b>Publishing options:</b>'),
      '#suffix' => '</p>',
      '#title' => t('Send to all affiliates'),
      '#required' => FALSE,
      '#description' => t('Select if this content can be shown to all affiliates.  This setting will override the options below.'),
      '#default_value' => variable_get('domain_behavior', DOMAIN_INSTALL_RULE),
    );
    $form['domain']['domains'] = array(
      '#type' => empty($format) ? 'checkboxes' : 'select',
      '#title' => t('Publish to'),
      '#options' => $options,
      '#required' => FALSE,
      '#description' => t('Select which affiliates can access this content.'),
      '#default_value' => array(($_domain['domain_id'] == 0) ? -1 : $_domain['domain_id']), // Can't use 0 as a checkbox value.
    );
    if ($format) {
      $form['domain']['domains']['#multiple'] = TRUE;
      $form['domain']['domains']['#size'] = count($options) > 10 ? 10 : count($options);
    }
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
  $form['#submit'][] = 'domain_content_update_nodes';
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
 * FormAPI function that lets us update access rules.
 */
function domain_content_update_nodes($form, &$form_state) {
  static $load_nodes;

  // If our operation is flagged, then we have to manually change the
  // {node_access} table.  The rest of the process will clear the cache,
  // so this should be a safe operation.
  if ($form_state['values']['operation'] != 'domain') {
    return;
  }

  // Set the site grant.
  $domain_site = FALSE;
  if ($form_state['values']['domain_site']) {
    $domain_site = TRUE;
  }

  // Filter the domain input.
  $domains = array_filter($form_state['values']['domains']);

  // Do other modules have a stake in the node grants set by Domain Access?
  if (!isset($load_nodes)) {
    $load_nodes = (bool) count(module_implements('domainrecords'));
  }

  // Loop through the selected nodes and rewrite the records.
  foreach ($form_state['values']['nodes'] as $nid) {
    // If other modules need to respond, we have to load the full node.
    if ($load_nodes) {
      $node = node_load($nid);
    }
    else {
      $node = new stdClass();
      $node->nid = $nid;
    }
    // Make sure the node is valid.
    if ($node->nid > 0) {
      // If modifying values, do so here.
      if (!empty($form_state['values']['behavior'])) {
        $current = domain_get_node_domains($node->nid);
        // Add values to the current set.
        if ($form_state['values']['behavior'] == 1) {
          if (!empty($current['domain_site'])) {
            $domain_site = TRUE;
          }
          $domains += $current['domain_id'];
        }
        // Remove values from the current set.
        else {
          foreach ($domains as $domain_id) {
            if (isset($current['domain_id'][$domain_id])) {
              unset($current['domain_id'][$domain_id]);
            }
          }
          $domains = $current['domain_id'];
          // If all affiliates is selected, remove it.
          if ($domain_site) {
            $domain_site = FALSE;
          }
        }
      }
      // Use our new options, as set above.
      $node->domain_site = $domain_site;
      $node->domains = $domains;
      // Run our storage hook. This handles {domain_access} and returns
      // the records we need for {node_access}.
      $grants = domain_node_access_records($node);
      // Get all the grants returned.
      $realms = array('domain_site', 'domain_id');
      foreach ($grants as $grant) {
        if (!in_array($grant['realm'], $realms)) {
          $realms[] = $grant['realm'];
        }
      }
      // Delete our grants and rebuild.
      foreach ($realms as $realm) {
        node_access_write_grants($node, $grants, $realm);
      }
    }
  }
  // Clear the cache.
  cache_clear_all();
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
  $output .= drupal_render($form['options']);
  $output .= drupal_render($form['domain']);
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
