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
 *   A link group for each domain the user can access.
 */
function domain_content_page() {
  $build = array();
  $content = array();
  $content = system_admin_menu_block(menu_get_item('admin/domain/content'));
  // Print the list of options.
  if (!empty($content)) {
    $output = theme('admin_block_content', array('content' => $content));
  }
  // There was an error or invalid request.
  else {
    $output = t('There are no valid domains configured.');
  }
  $build['content'] = array(
    '#markup' => $output,
  );
  return $build;
}

/**
 * List the available domains for this user.
 *
 * See http://drupal.org/node/367752 for a discussion of the
 * need for this function.
 */
function domain_content_list() {
  global $user;
  $show_all = user_access('bypass node access') || user_access('review content for all domains');

  if (!$show_all) {
    if (empty($user->domain_user)) {
      return drupal_access_denied();
    }
    $user_domains = array_filter($user->domain_user);
    // We don't bother to unset the -1 here, since it won't matter to the query.
    // Cast the -1 as domain 0 for the query.
    if (!empty($user->domain_user[-1])) {
      $user_domains[] = 0;
    }
  }
  // Table information
  $header = array(
    array('data' => t('Id'), 'field' => 'domain_id'),
    array('data' => t('Site content'), 'field' => 'sitename'),
    array('data' => t('Content count')),
    array('data' => t('Site'), 'field' => 'subdomain'),
  );
  // Set up the base query.
  $query = db_select('domain', 'd')
    ->fields('d', array('domain_id', 'sitename', 'subdomain', 'scheme', 'valid'));
  // Filter by user access to assigned domains.
  if (!$show_all) {
    $query->condition('domain_id', $user_domains, 'IN');
  }
  // Add the tablesort.
  $query = $query
    ->extend('TableSort')
    ->orderByHeader($header);
  $query = $query->extend('PagerDefault')->limit(variable_get('domain_list_size', DOMAIN_LIST_SIZE));
  // Get the domains.
  $result = $query->execute();
  foreach ($result as $data) {
    $domains[] = (array) $data;
  }

  // Create the table.
  $rows = array();
  foreach ($domains as $domain) {
    $path = trim(domain_get_path($domain), '/');
    $rows[] = array(
      $domain['domain_id'],
      l(t('@sitename content', array('@sitename' => $domain['sitename'])), 'admin/domain/content/'. $domain['domain_id']),
      number_format((int) db_query("SELECT COUNT(nid) FROM {domain_access} WHERE gid = :gid AND realm = :realm", array(':gid' => $domain['domain_id'], ':realm' => 'domain_id'))->fetchField()),
      l(t('view site'), $path),
    );
  }

  // Add entry for 'all affiliates'
  $all = array(
    '-',
    l(t('Content assigned to all affiliates'), 'admin/domain/content/all'),
    number_format((int) db_query("SELECT COUNT(nid) FROM {domain_access} WHERE gid = :gid AND realm = :realm", array(':gid' => 0, ':realm' => 'domain_site'))->fetchField()),
    '',
  );
  array_unshift($rows, $all);

  // Build result table for UI
  $build = array();
  if (!empty($rows)) {
    $output = '<p>'. t('The table below shows all the affiliates sites whose content you may edit. Click on the site name link to see all content assigned to that affiliate.') .'</p>';
    $build['header'] = array(
      '#markup' => $output,
    );
    $build['content'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' =>  array('id' => 'domain-list'),
    );
    $build['pager']['#theme'] = 'pager';
  }
  else {
    $build['content'] = array(
      '#markup' => t('You do not have editing access to any domains. Please contact your site administrator.'),
    );
  }
  return $build;
}

/**
 * Content administration for a specific domain.
 * This callback puts the user on the current domain and then
 * fetches the appropirate content for batch editing.
 *
 * @param $domain_id
 *   The unique identifier for the currently active domain.
 * @param $all_affiliates
 *   A boolean flag that indicates whether to grant the domain_site node access
 *   realm for this content view.
 * @return
 *   A link group for each domain the user can access.
 */
function domain_content_view($domain_id = NULL, $all_affiliates = FALSE) {
  $build = array();
  $_domain = domain_get_domain();
  // Load the active domain, which is not necessarily the current domain.
  if (!is_null($domain_id) && $domain_id != $_domain['domain_id']) {
    domain_set_domain($domain_id);
    $_domain = domain_get_domain();
  }
  $output = '';
  // Override the $_domain global so we can see the appropriate content
  if (!is_null($domain_id)) {
    $_domain['site_grant'] = FALSE;
    drupal_set_title(t('Content for @domain', array('@domain' => $_domain['sitename'])));
  }
  elseif ($all_affiliates) {
    $_domain['site_grant'] = TRUE;
    drupal_set_title(t('Content for all affiliate sites'));
  }

  // KILLSWITCH CASE: returns an error
  else {
    drupal_set_message(t('Invalid request'), 'error');
    $build['content'] = array(
      '#markup' => t('<p>The specified domain does not exist.</p>'),
    );
    return $build;
  }
  $output = drupal_get_form('domain_content_admin');
  // Reset the active domain.
  domain_reset_domain();
  return $output;
}

/**
 * Content admin page callback.
 *
 * @return
 *   A themed HTML batch content editing form.
 */
function domain_content_admin() {
  // Load the form basics for administering nodes.
  include_once drupal_get_path('module', 'node') .'/node.admin.inc';
  $form = array();
  $form['#attached']['css'] = array(drupal_get_path('module', 'domain_content') . '/domain_content.css');

  if (isset($form_state['values']['operation']) && $form_state['values']['operation'] == 'delete') {
    return node_multiple_delete_confirm($form, $form_state, array_filter($form_state['values']['nodes']));
  }
  $form['filter'] = node_filter_form();
  $form['#submit'][] = 'node_filter_form_submit';

  // Privileged users can make global changes to Domain Access permissions.
  if (user_access('set domain access')) {
    domain_content_add_form_widget($form);
  }
  $form['admin'] = domain_content_form();

  $form['admin']['options']['submit']['#submit'][] = 'domain_content_process_nodes';
  // Filter the available operations based on user permissions.
  domain_content_filter_operations($form['admin']['options']['operation']['#options']);

  return $form;
}

/**
 * Rewrites node_admin_nodes() to use db_rewrite_sql().
 *
 * @return
 *   A form array according to the FormsAPI.
 */
function domain_content_form() {
  module_load_include('inc', 'node', 'node.admin');
  $_domain = domain_get_domain();
  $admin_access = user_access('administer nodes');

  // Build the 'Update options' form.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
    '#access' => $admin_access,
  );
  $options = array();
  foreach (module_invoke_all('node_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => 'approve',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#validate' => array('node_admin_nodes_validate'),
    '#submit' => array('node_admin_nodes_submit'),
  );

  // Enable language column if translation module is enabled
  // or if we have any node with language.
  $multilanguage = (module_exists('translation') || db_query("SELECT 1 FROM {node} WHERE language <> :language", array(':language' => LANGUAGE_NONE))->fetchField());

  // Build the sortable table header.
  $header = array(
    'title' => array('data' => t('Title'), 'field' => 'n.title'),
    'domains' => array('data' => t('Affiliates')),
    'type' => array('data' => t('Type'), 'field' => 'n.type'),
    'author' => t('Author'),
    'status' => array('data' => t('Status'), 'field' => 'n.status'),
    'changed' => array('data' => t('Updated'), 'field' => 'n.changed', 'sort' => 'desc')
  );

  if ($multilanguage) {
    $header['language'] = array('data' => t('Language'), 'field' => 'n.language');
  }
  $header['operations'] = array('data' => t('Operations'));

  $query = db_select('node', 'n')->extend('PagerDefault')->extend('TableSort');
  node_build_filter_query($query);

  if (!user_access('bypass node access')) {
    // If the user is able to view their own unpublished nodes, allow them
    // to see these in addition to published nodes. Check that they actually
    // have some unpublished nodes to view before adding the condition.
    if (user_access('view own unpublished content') && $own_unpublished = db_query('SELECT nid FROM {node} WHERE uid = :uid AND status = :status', array(':uid' => $GLOBALS['user']->uid, ':status' => 0))->fetchCol()) {
      $query->condition(db_or()
        ->condition('n.status', 1)
        ->condition('n.nid', $own_unpublished, 'IN')
      );
    }
    else {
      // If not, restrict the query to published nodes.
      $query->condition('n.status', 1);
    }
  }
  // Restrict the query to the active domain.
  domain_alter_node_query($query, FALSE);

  $nids = $query
    ->fields('n',array('nid'))
    ->limit(50)
    ->orderByHeader($header)
    ->execute()
    ->fetchCol();
  $nodes = node_load_multiple($nids);

  // Prepare the list of nodes.
  $languages = language_list();
  $destination = drupal_get_destination();
  $options = array();
  foreach ($nodes as $node) {
    $l_options = $node->language != LANGUAGE_NONE ? array('language' => $languages[$node->language]) : array();
    $options[$node->nid] = array(
      'title' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $node->title,
          '#href' => 'node/' . $node->nid,
          '#options' => $l_options,
          '#suffix' => ' ' . theme('mark', array('type' => node_mark($node->nid, $node->changed))),
        ),
      ),
      'type' => check_plain(node_type_get_name($node)),
      'author' => theme('username', array('account' => $node)),
      'status' => $node->status ? t('published') : t('not published'),
      'changed' => format_date($node->changed, 'short'),
    );
    if ($multilanguage) {
      $options[$node->nid]['language'] = $node->language == LANGUAGE_NONE ? t('Language neutral') : t($languages[$node->language]->name);
    }
    // Build a list of all the accessible operations for the current node.
    $operations = array();
    if (node_access('update', $node)) {
      $operations['edit'] = array(
        'title' => t('edit'),
        'href' => 'node/' . $node->nid . '/edit',
        'query' => $destination,
      );
    }
    if (node_access('delete', $node)) {
      $operations['delete'] = array(
        'title' => t('delete'),
        'href' => 'node/' . $node->nid . '/delete',
        'query' => $destination,
      );
    }
    $options[$node->nid]['operations'] = array();
    if (count($operations) > 1) {
      // Render an unordered list of operations links.
      $options[$node->nid]['operations'] = array(
        'data' => array(
          '#theme' => 'links__node_operations',
          '#links' => $operations,
          '#attributes' => array('class' => array('links', 'inline')),
        ),
      );
    }
    elseif (!empty($operations)) {
      // Render the first and only operation as a link.
      $link = reset($operations);
      $options[$node->nid]['operations'] = array(
        'data' => array(
          '#type' => 'link',
          '#title' => $link['title'],
          '#href' => $link['href'],
          '#options' => array('query' => $link['query']),
        ),
      );
    }
    // Set the assigned domains.
    $options[$node->nid]['domains'] = domain_content_view_domains($node);
  }
  // Only use a tableselect when the current user is able to perform any
  // operations.
  if ($admin_access) {
    $form['nodes'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No content available.'),
    );
  }
  // Otherwise, use a simple table.
  else {
    $form['nodes'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $options,
      '#empty' => t('No content available.'),
    );
  }

  $form['pager'] = array('#markup' => theme('pager', array('tags' => NULL)));

  return $form;
}

/**
 * Format domains for this node.
 */
function domain_content_view_domains($node) {
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
      $source = db_query("SELECT domain_id FROM {domain_source} WHERE nid = :nid", array(':nid' => $node->nid))->fetchField();
      if (!empty($source) && isset($items[$source])) {
        $items[$source] .= '*';
      }
    }
    return theme('item_list', array('items' => $items));
}

/**
 * Filters the node batch operations based on the user's permissions.
 *
 * @param &$operations
 *   The complete list of operations, passed by reference.
 * @return
 *   No return value. Modify by reference.
 */
function domain_content_filter_operations(&$operations) {
  $settings = variable_get('domain_form_elements', array('options', 'delete', 'comment_settings', 'path'));
  // Administer nodes can do anything.
  if (user_access('administer nodes')) {
    return;
  }
  // You must be able to edit to view this page, so check for the new delete perm.
  if (!user_access('delete domain content')) {
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
