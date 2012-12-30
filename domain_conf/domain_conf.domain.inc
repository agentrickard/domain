<?php

/**
 * @file
 * Domain hooks for Domain Conf.
 *
 * @ingroup domain_conf
 */

/**
 * Implements hook_domain_warning().
 */
function domain_conf_domain_warning() {
  // These are the forms for variables set by Domain Conf.
  $forms = array(
    'system_admin_theme_settings',
    'system_performance_settings',
    'system_regional_settings',
    'system_site_information_settings',
    'system_site_maintenance_mode',
    'locale_languages_overview_form',
    'menu_configure',
    'node_type_form',
  );
  $return = array();
  foreach ($forms as $form) {
    $return[$form] = 'admin/structure/domain/view/%domain_id/config';
  }
  return $return;
}

/**
 * Implements hook_domain_batch().
 */
function domain_conf_domain_batch() {
  $batch = array();
  // Allows the deletion of all Domain Configuration rows.
  $batch['domain_conf'] = array(
    '#form' => array(
      '#title' => t('Reset configurations'),
      '#type' => 'checkbox',
      '#options' => array(0 => 1, 1 => t('Reset')),
      '#description' => t('Delete custom settings for this domain.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_delete',
    '#system_default' => 0,
    '#variable' => 'domain_conf',
    '#meta_description' => t('Delete custom settings for domains as supplied by Domain Configuration.'),
    '#table' => 'domain_conf',
    '#weight' => -10,
  );
  // Change the site name.
  $batch['site_name'] = array(
    '#form' => array(
      '#title' => t('Site name'),
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => t('The site name for this domain. Usually overridden by the domain name.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('site_name', ''),
    '#variable' => 'site_name',
    '#meta_description' => t('Set the site name for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Site configuration'),
  );
  // Change the email address.
  $batch['site_mail'] = array(
    '#form' => array(
      '#title' => t('Email address'),
      '#type' => 'textfield',
      '#size' => 40,
      '#maxlength' => 255,
      '#description' => t('Set the email address for this domain.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('site_mail', ''),
    '#variable' => 'site_mail',
    '#meta_description' => t('Set the email address for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Site configuration'),
  );
  // Change the site slogan.
  $batch['site_slogan'] = array(
    '#form' => array(
      '#title' => t('Site slogan'),
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => t('The slogan of this domain. Some themes display a slogan when available.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('site_slogan', ''),
    '#variable' => 'site_slogan',
    '#meta_description' => t('Set the site slogan for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Site configuration'),
  );
  // Change the site front page.
  $batch['site_frontpage'] = array(
    '#form' => array(
      '#title' => t('Site frontpage'),
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 255,
      '#description' => t('The home page displays content from this relative URL. If unsure, specify "node".'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('site_frontpage', 'node'),
    '#variable' => 'site_frontpage',
    '#meta_description' => t('Set the site frontpage for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Site configuration'),
  );
  // Change the anonymous user name.
  $batch['anonymous'] = array(
    '#form' => array(
      '#title' => t('Anonymous user'),
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 255,
      '#description' => t('The name used to indicate anonymous users for this domain.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('anonymous', 'Anonymous'),
    '#variable' => 'anonymous',
    '#meta_description' => t('Set the anonymous user label for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Site configuration'),
  );
  // Change the administrative theme.
  $themes = list_themes();
  ksort($themes);
  $options[] = t('Use domain default theme');
  foreach ($themes as $key => $value) {
    $options[$key] = $key;
  }
  $batch['admin_theme'] = array(
    '#form' => array(
      '#title' => t('Administrative theme'),
      '#type' => 'select',
      '#options' => $options,
      '#description' => t('Select the administrative theme for this domain.'),
    ),
    '#permission' => 'administer themes',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('admin_theme', 0),
    '#variable' => 'admin_theme',
    '#meta_description' => t('Set the administrative theme for all domains.'),
    '#data_type' => 'string',
    '#weight' => -8,
    '#update_all' => TRUE,
    '#group' => t('Administrative theme'),
  );
  // Change the timezone.
  $zones = system_time_zones();
  $batch['date_default_timezone'] = array(
    '#form' => array(
      '#title' => t('Timezone default'),
      '#type' => 'select',
      '#options' => $zones,
      '#description' => t('Select the default site time zone.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('date_default_timezone', 0),
    '#variable' => 'date_default_timezone',
    '#meta_description' => t('Set the default timezone for all domains.'),
    '#data_type' => 'string',
    '#weight' => -6,
    '#update_all' => TRUE,
    '#group' => t('Timezone settings'),
  );
  // Change the caching mode.
  $cache = variable_get('cache', 0);
  $batch['cache'] = array(
    '#form' => array(
      '#type' => 'checkbox',
      '#title' => t('Cache pages for anonymous users'),
      '#default_value' => $cache,
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => $cache,
    '#variable' => 'cache',
    '#meta_description' => t('Set the page cache options for all domains.'),
    '#data_type' => 'integer',
    '#weight' => -5,
    '#update_all' => TRUE,
    '#group' => t('Performance'),
  );
  // Change the cache lifetime.
  $period = drupal_map_assoc(array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400), 'format_interval');
  $period[0] = '<' . t('none') . '>';
  $batch['cache_lifetime'] = array(
    '#form' => array(
      '#type' => 'select',
      '#title' => t('Minimum cache lifetime'),
      '#default_value' => variable_get('cache_lifetime', 0),
      '#options' => $period,
      '#description' => t('The minimum amount of time that will elapse before the caches are recreated.')
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('cache_lifetime', 0),
    '#variable' => 'cache_lifetime',
    '#meta_description' => t('Set the minimum cache lifetime for all domains.'),
    '#data_type' => 'integer',
    '#weight' => -5,
    '#update_all' => TRUE,
    '#group' => t('Performance'),
  );
  // Toggle the site offline status.
  $batch['maintenance_mode'] = array(
    '#form' => array(
      '#type' => 'checkbox',
      '#title' => t('Put site into maintenance mode'),
      '#default_value' => variable_get('maintenance_mode', 0),
      '#description' => t('When enabled, only users with the "Access site in maintenance mode" <a href="@permissions-url">permission</a> are able to access your site to perform maintenance; all other visitors see the maintenance mode message configured below. Authorized users can log in directly via the <a href="@user-login">user login</a> page.', array('@permissions-url' => url('admin/people/permissions'), '@user-login' => url('user'))),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => variable_get('maintenance_mode', 0),
    '#variable' => 'maintenance_mode',
    '#meta_description' => t('Set maintenance for all domains.'),
    '#data_type' => 'integer',
    '#weight' => -4,
    '#update_all' => TRUE,
    '#group' => t('Maintenance mode'),
  );
  // Change the site offline message.
  $batch['maintenance_mode_message'] = array(
    '#form' => array(
      '#title' => t('Site offline message'),
      '#type' => 'textarea',
      '#cols' => 30,
      '#rows' => 5,
      '#description' => t('Message to show visitors when this domain is in off-line mode.'),
    ),
    '#permission' => 'administer site configuration',
    '#domain_action' => 'domain_conf',
    '#system_default' => t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal'))),
    '#variable' => 'maintenance_mode_message',
    '#meta_description' => t('Set the site offline message for all domains.'),
    '#data_type' => 'string',
    '#weight' => -2,
    '#update_all' => TRUE,
    '#group' => t('Maintenance mode'),
  );
  // Change the default language.
  if (module_exists('locale')) {
    $languages = domain_conf_language_options();
    $default = language_default();
    $batch['language_default'] = array(
      '#form' => array(
        '#title' => t('Default language'),
        '#type' => 'select',
        '#options' => $languages,
        '#description' => t('The default language to use for this domain. <em>Note: This setting only works with path-based language switching.</em>'),
      ),
      '#permission' => 'administer languages',
      '#domain_action' => 'domain_conf',
      '#system_default' => $default->language,
      '#override_default' => TRUE,
      '#variable' => '', // deliberately empty, since this is an array and cannot be set.
      '#meta_description' => t('Set the default language for all domains.'),
      '#data_type' => 'string',
      '#weight' => 6,
      '#update_all' => TRUE,
      '#group' => t('Language settings'),
      '#module' => t('Language'),
    );
  }
  // Change the menus
  if (module_exists('menu')) {
    $menus = menu_get_menus();
    $menu_options = $menus;
    $menus[0] = t('Do not use for this site');
    $main = variable_get('menu_main_links_source', 'main-menu');
    $batch['menu_main_links_source'] = array(
      '#form' => array(
        '#title' => t('Source for the Main links'),
        '#type' => 'select',
        '#options' => $menus,
        '#description' => t('Select what should be displayed as the Main links (typically at the top of the page).'),
      ),
      '#permission' => 'administer menu',
      '#domain_action' => 'domain_conf',
      '#system_default' => $main,
      '#variable' => 'menu_main_links_source',
      '#meta_description' => t('Select what should be displayed as the Main links (typically at the top of the page).'),
      '#data_type' => 'string',
      '#weight' => 2,
      '#update_all' => TRUE,
      '#group' => t('Menu settings'),
      '#module' => t('Menu'),
    );
    $batch['menu_secondary_links_source'] = array(
      '#form' => array(
        '#title' => t('Source for the Secondary links'),
        '#type' => 'select',
        '#options' => $menus,
        '#description' => t("Select the source for the Secondary links. An advanced option allows you to use the same source for both Main links (currently %main) and Secondary links: if your source menu has two levels of hierarchy, the top level menu links will appear in the Main links, and the children of the active link will appear in the Secondary links." , array('%main' => isset($menus[$main]) ? $menus[$main] : t('No Main menu'))),
      ),
      '#permission' => 'administer menu',
      '#domain_action' => 'domain_conf',
      '#system_default' => variable_get('menu_secondary_links_source', 0),
      '#variable' => 'menu_secondary_links_source',
      '#meta_description' => t('Set the secondary links menu in all domains.'),
      '#data_type' => 'string',
      '#weight' => 4,
      '#update_all' => TRUE,
      '#group' => t('Menu settings'),
      '#module' => t('Menu'),
    );
    $types = node_type_get_types();
    // Node menu handling is much more complex now.
    // Call Drupal.menu_update_parent_list() to filter the list of
    // available default parent menu items based on the selected menus.
    // This function will not work with multiple form elements on the page.
    // TODO: Replace or extend the core JS.
    /*
    drupal_add_js(
      '(function ($) { Drupal.menu_update_parent_list(); })(jQuery);',
      array('scope' => 'footer', 'type' => 'inline')
    );
    drupal_add_js(drupal_get_path('module', 'menu') . '/menu.admin.js'));
    */
    // To avoid an 'illegal option' error after saving the form we have to load
    // all available menu items. Otherwise it is not possible to dynamically add
    // options to the list.
    foreach ($types as $type) {
      $form['menu']['menu_options'] = array(
        '#type' => 'checkboxes',
        '#title' => t('@type: Available menus', array('@type' => $type->name)),
        '#options' => $menu_options,
      );
      $form['menu']['menu_parent'] = array(
        '#type' => 'select',
        '#title' => t('@type: Default parent item', array('@type' => $type->name)),
        '#options' => array(),
        '#attributes' => array('class' => array('menu-title-select')),
        '#process' => array('domain_conf_get_menu_parents'),
      );
      $batch['menu_options_' . $type->type] = array(
        '#form' => $form['menu']['menu_options'],
        '#permission' => 'administer menu',
        '#domain_action' => 'domain_conf',
        '#system_default' => variable_get('menu_options_' . $type->type, array('main-menu')),
        '#variable' => 'menu_options_' . $type->type,
        '#meta_description' => t('The menus available to place links in for this content type.'),
        '#data_type' => 'string',
        '#weight' => 6,
        '#update_all' => TRUE,
        '#collapsed' => TRUE,
        '#group' => t('@type menu settings', array('@type' => $type->name)),
        '#module' => t('Menu'),
      );
      $batch['menu_parent_' . $type->type] = array(
        '#form' => $form['menu']['menu_parent'],
        '#permission' => 'administer menu',
        '#domain_action' => 'domain_conf',
        '#system_default' => variable_get('menu_parent_' . $type->type, 'main-menu:0'),
        '#variable' => 'menu_parent_' . $type->type,
        '#meta_description' => t('Choose the menu item to be the default parent for a new link in the content authoring form.'),
        '#data_type' => 'string',
        '#weight' => 6,
        '#update_all' => TRUE,
        '#collapsed' => TRUE,
        '#group' => t('@type menu settings', array('@type' => $type->name)),
        '#module' => t('Menu'),
      );
    }
  }
  foreach ($batch as $key => $value) {
    if (!isset($batch[$key]['#module'])) {
      $batch[$key]['#module'] = t('Domain Configuration');
    }
  }

  return $batch;
}

/**
 * Implements hook_domain_delete().
 */
function domain_conf_domain_delete($domain, $form_values = array()) {
  db_delete('domain_conf')
    ->condition('domain_id', $domain['domain_id'])
    ->execute();
  cache_clear_all('variables', 'cache_bootstrap');
}

/**
 * Form process function to load available menus.
 *
 * If we try to load the menus in the batch hook, it can create a race
 * condition with menu_rebuild() that leads to a fatal call stack error.
 *
 * So we use this process callback to populate the menus safely, after they
 * have been rebuilt.
 */
function domain_conf_get_menu_parents($element, &$form_state) {
  static $options;
  if (!isset($options)) {
    $options = menu_parent_options(menu_get_menus(), array('mlid' => 0));
  }
  $element['#options'] += $options;
  return $element;
}
