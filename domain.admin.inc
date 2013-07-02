<?php

/**
 * @file
 * Administrative pages for the Domain module.
 */

use Drupal\domain\DomainInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form builder to list domain records.
 *
 * @TODO: move to a formController.
 */
function domain_overview_form($form, &$form_state, $domains) {
  $active_domain = domain_get_domain();
  $form['#attached']['library'][] = array('system', 'drupal.tableheader');

  $header = array(
    array('data' => t('Order')),
    array('data' => t('Weight')),
    array('data' => t('Name')),
    array('data' => t('Hostname')),
    array('data' => t('Status')),
    array('data' => t('Default domain')),
    array('data' => t('Operations')),
  );

  $form['#tree'] = TRUE;
  // @TODO: re-order this form structure.
  $form['domains'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#empty' => t('There are no domains yet. <a href="@add-url">Add a domain.</a>', array(
      '@add-url' => url('admin/structure/domain/add'),
    )),
    '#tabledrag' => array(
      array('order', 'sibling', 'domain-weight'),
    ),
  );

  // @TODO page limit setting.
  // Get the count of all domains, which may be paginated.
  $destination = drupal_get_destination();
  $delta = count(domain_load_multiple());
  $limit = 10;
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  $query = db_select('domain', 'd')
    ->fields('d', array('domain_id'))
    ->orderBy('weight')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit($limit);
  // Filter by domains, if present and not all domains.
  if (count($domains) != $delta && $keys = array_keys($domains)) {
    $query->condition('domain_id', $keys, 'IN');
  }
  // Get the domains.
  $result = $query->execute();
  $i = 0;
  foreach ($result as $domain) {
    $domain_id = $domain->domain_id;
    $domain = entity_load('domain', $domain_id, TRUE);
    $form['domains'][$domain->machine_name]['#attributes']['class'][] = 'draggable';
    $form['domains'][$domain->machine_name]['values'] = array(
      '#type' => 'value',
      '#value' => $domain,
    );
    $form['domains'][$domain->machine_name]['weight'] = array(
      '#type' => 'weight',
      '#default_value' => $domain->get('weight'),
      '#delta' => $delta,
      '#attributes' => array('class' => array('domain-weight')),
    );
    $form['domains'][$domain->machine_name]['name'] = array(
      '#type' => 'markup',
      '#markup' => ($domain->isActive()) ? '<strong>' . check_plain($domain->name) . '</strong>' : check_plain($domain->name),
    );
    $form['domains'][$domain->machine_name]['hostname'] = array(
      '#type' => 'markup',
      '#markup' => ($domain->isActive()) ? '<strong>' . l($domain->hostname, $domain->url) . '</strong>' : l($domain->hostname, $domain->url),
    );
    $form['domains'][$domain->machine_name]['status'] = array(
      '#type' => 'markup',
      '#markup' => !empty($domain->status) ? t('Active') : t('Inactive'),
    );
    $form['domains'][$domain->machine_name]['is_default'] = array(
      '#type' => 'markup',
      '#markup' => !empty($domain->is_default) ? t('Default') : '',
    );
    // @TODO: permissions on these items.
    $operations = array();
    $operations['edit'] = array(
      'title' => t('edit'),
      'href' => "admin/structure/domain/$domain->machine_name",
      'query' => array(),
    );
    if ($domain->status && !$domain->is_default) {
      $operations['disable'] = array(
        'title' => t('disable'),
        'href' => "admin/structure/domain/$domain->machine_name/disable",
        'query' => array('token' => drupal_get_token()),
      );
    }
    elseif (!$domain->is_default) {
      $operations['enable'] = array(
        'title' => t('enable'),
        'href' => "admin/structure/domain/$domain->machine_name/enable",
        'query' => array('token' => drupal_get_token()),
      );
    }
    if (!$domain->is_default) {
      $operations['default'] = array(
        'title' => t('make default'),
        'href' => "admin/structure/domain/$domain->machine_name/default",
        'query' => array('token' => drupal_get_token()),
      );
      $operations['delete'] = array(
        'title' => t('delete'),
        'href' => "admin/structure/domain/$domain->machine_name/delete",
        'query' => array(),
      );
    }
    Drupal::moduleHandler()->invokeAll('domain_operations', $operations);
    foreach ($operations as $key => $value) {
      $operations[$key]['query'] += $destination;
    }
    $form['domains'][$domain->machine_name]['operations'] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $operations,
      ),
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save domains'),
  );
  return $form;
}

/**
 * Submit handler for the domain overview form.
 */
function domain_overview_form_submit($form, &$form_state) {
  $values = $form_state['values']['domains'];
  foreach ($values as $machine_name => $value) {
    db_update('domain')
      ->fields(array('weight' => $value['weight']))
      ->condition('machine_name', $machine_name)
      ->execute();
    }

  drupal_set_message(t('Domain settings updated'));
}

/**
 * Page callback to create a new domain record.
 *
 * @param $inherit
 *   Boolean value indicating that form values should inherit from the current
 *   request.
 *
 * @see domain_create()
 */
function domain_add($inherit = FALSE) {
  $domain = domain_create($inherit);
  return entity_get_form($domain);
}

/**
 * Page callback to edit a domain record.
 */
function domain_edit($domain) {
  return entity_get_form($domain);
}

/**
 * Page callback to edit a domain record.
 */
function domain_delete($domain) {
  return entity_get_form($domain);
}

/**
 * Performs click actions on the domain overview form.
 *
 * @param Drupal\domain\DomainInterface
 *   A domain record object.
 * @param $action
 *   The name of an action to perform.
 *   See Drupal\domain\Plugins\Action
 */
function domain_admin_callback(DomainInterface $domain, $action) {
  // @todo CSRF tokens are validated in page callbacks rather than access
  //   callbacks, because access callbacks are also invoked during menu link
  //   generation. Add token support to routing: http://drupal.org/node/755584.
  $token = drupal_container()->get('request')->query->get('token');
  $allowed_actions = array('enable', 'disable', 'default');

  if (!in_array($action, $allowed_actions) || !isset($token) || !drupal_valid_token($token)) {
    throw new AccessDeniedHttpException();
  }

  $success = FALSE;
  switch($action) {
    case 'default':
      $domain->saveDefault();
      $verb = t('set as default');
      if ($domain->isDefault()) {
        $success = TRUE;
      }
      break;
    case 'enable':
      $domain->enable();
      $verb = t('has been enabled.');
      if ($domain->status) {
        $success = TRUE;
      }
      break;
    case 'disable':
      $domain->disable();
      $verb = t('has been disabled.');
      if (!$domain->status) {
        $success = TRUE;
      }
      break;
  }
  if ($success) {
    drupal_set_message(t('Domain record !verb.', array('!verb' => $verb)));
  }
  // Return to the invoking page.
  $destination = drupal_get_destination();
  return new RedirectResponse($destination['destination']);
}
