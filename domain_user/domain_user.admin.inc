<?php

/**
 * @file
 * Admin screens for Domain User.
 *
 * @ingroup domain_user
 */

 /**
 * FormsAPI for module settings
 */
function domain_user_configure_form() {
  drupal_set_title(t('User domain settings'));
  $form = array();
  $form['domain_user'] = array(
    '#type' => 'radios',
    '#title' => t('Module behavior'),
    '#options' => array(0 => t('Do not create domains for users'), 1 => t('Automatically create domains for new users'), 2 => t('Ask users if they would like to create a domain')),
    '#description' => t('Should subdomains be created when users register?'),
    '#default_value' => variable_get('domain_user', 0),
  );
  $form['domain_user_root'] = array(
    '#type' => 'textfield',
    '#title' => t('Root domain name'),
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#default_value' => variable_get('domain_user_root', variable_get('domain_root', '')),
    '#description' => t('The root domain to use for creating user domains, typically <em>example.com</em>.  No http or slashes.
      <br /> When users create domains, their username will be added to the root domain to create a custom domain.
      <br /> For example, <em>user1.example.com</em> or <em>administrator.example.com</em>.')
  );
  $form['domain_user_scheme'] = array(
    '#type' => 'radios',
    '#title' => t('User Domain URL scheme'),
    '#options' => array('http' => 'http://', 'https' => 'https://'),
    '#default_value' => variable_get('domain_user_scheme', 'http'),
    '#description' => t('The URL scheme for accessing user domains.')
  );
  $form['domain_user_login'] = array(
    '#type' => 'radios',
    '#title' => t('User login behavior'),
    '#options' => array(1 => t('On login, go to personal domain'), 0 => t('Do not go to personal domain on login')),
    '#default_value' => variable_get('domain_user_login', 1),
    '#description' => t('The domain users should go to when they login to the site.')
  );
  $form['domain_user_assign'] = array(
    '#type' => 'radios',
    '#title' => t('Assign user domains'),
    '#options' => array(0 => t('Only assign to user domain'), 1 => t('Assign to both user domain and active domain')),
    '#default_value' => variable_get('domain_user_assign', 0),
    '#description' => t('Indicates which domains should the user be assigned to when new user accounts are created.')
  );
  if (module_exists('domain_prefix')) {
    $form['domain_user_prefixing'] = array(
      '#type' => 'radios',
      '#title' => t('Domain table prefixing'),
      '#options' => array(0 => t('Never create prefixed tabled for user domains'), 1 => t('Obey the settings in Domain Prefix')),
      '#description' => t('Should user domains have detabase talbes created?'),
      '#default_value' => variable_get('domain_user_prefixing', 0),
    );
  }
  // Show the rules for username restrictions
  $rules =  domain_user_rules();
  if (!empty($rules)) {
    $output = '<ul>';
    foreach ($rules as $rule) {
      $output .= '<li>'. $rule .'</li>';
    }
    $output .= '</ul>';
  }
  $form['rules'] = array(
    '#type' => 'markup',
    '#weight' => 20,
    '#value' => '<br /><br />'. t('<h3>Reserved Usernames</h3><p>The following usernames cannot be registered, since they are used as unique domains:</p>') . $output,
  );
  return system_settings_form($form);
}
