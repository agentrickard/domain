<?php

/**
 * @file
 * Builds placeholder replacement tokens for domain-related data.
 */

/**
 * Implements hook_token_info().
 */
function domain_token_info() {
  // Domain token types.
  $info['types']['domain'] = array(
    'name' => t('Domains'),
    'description' => t('Tokens related to domains.'),
    'needs-data' => 'domain',
  );
  $info['types']['current-domain'] = array(
    'name' => t('Current domain'),
    'description' => t('Tokens related to the current domain.'),
    'type' => 'domain',
  );
  $info['types']['default-domain'] = array(
    'name' => t('Default domain'),
    'description' => t('Tokens related to the default domain.'),
    'type' => 'domain',
  );

  // Domain tokens.
  $info['tokens']['domain']['id'] = array(
    'name' => t('Domain id'),
    'description' => t('The domain ID.'),
  );
  $info['tokens']['domain']['name'] = array(
    'name' => t('Domain name'),
    'description' => t('The domain name.'),
  );
  $info['tokens']['domain']['url'] = array(
    'name' => t('Domain URL'),
    'description' => t('The domain\'s URL, lowercased and with only alphanumeric characters.'),
  );
  $info['tokens']['domain']['subdomain'] = array(
    'name' => t('Subdomain'),
    'description' => t('The subdomain, lowercased and with only alphanumeric characters. Only works with *.example.com formats'),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function domain_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $sanitize = !empty($options['sanitize']);
  $replacements = array();

  if ($type == 'domain' && !empty($data['domain'])) {
    $domain = $data['domain'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $domain['domain_id'];
          break;
        case 'name':
          $replacements[$original] = $sanitize ? check_plain($domain['sitename']) : $domain['sitename'];
          break;
        case 'url':
          $url = domain_url_encode($domain['subdomain']);
          $replacements[$original] = $sanitize ? check_plain($url) : $url;
          break;
        case 'subdomain':
          $subdomain_elements = explode('.', $domain['subdomain']);
          if (count($subdomain_elements) > 2) {
            $subdomain = $subdomain_elements[0];
          }
          else {
            $subdomain = 'www';
          }
          $subdomain = domain_url_encode($subdomain);
          $replacements[$original] = $sanitize ? check_plain($subdomain) : $subdomain;
          break;
      }
    }
  }

  if ($type == 'current-domain') {
    $current_domain = domain_get_domain();
    $replacements += token_generate('domain', $tokens, array('domain' => $current_domain), $options);
  }

  if ($type == 'default-domain') {
    $default_domain = domain_default(FALSE);
    $replacements += token_generate('domain', $tokens, array('domain' => $default_domain), $options);
  }

  return $replacements;
}
