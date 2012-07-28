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
  $info['tokens']['domain']['machine-name'] = array(
    'name' => t('Domain machine name'),
    'description' => t('The domain machine identifier.'),
  );
  $info['tokens']['domain']['path'] = array(
    'name' => t('Domain path'),
    'description' => t('The base url path for the domain.'),
  );
  $info['tokens']['domain']['name'] = array(
    'name' => t('Domain name'),
    'description' => t('The domain name.'),
  );
  $info['tokens']['domain']['url'] = array(
    'name' => t('Domain URL'),
    'description' => t('The domain\'s URL, lower-cased and with only alphanumeric characters.'),
  );
  $info['tokens']['domain']['hostname'] = array(
    'name' => t('Domain hostname'),
    'description' => t('The domain hostname.'),
  );
  $info['tokens']['domain']['subdomain'] = array(
    'name' => t('Subdomain'),
    'description' => t('The subdomain, lower-cased and with only alphanumeric characters. Only works with *.example.com formats'),
  );

  $info['tokens']['node']['domain'] = array(
    'name' => t('Domain information'),
    'description' => t('The domain associated with this content.'),
    'type' => 'domain',
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function domain_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $sanitize = !empty($options['sanitize']);
  $replacements = array();

  // Base token handling.
  if ($type == 'domain' && !empty($data['domain'])) {
    $domain = $data['domain'];

    // Loop through the tokens and replace the elements.
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $domain['domain_id'];
          break;
        case 'machine-name':
        case 'machine_name': // Deprecated and renamed to 'machine-name'.
          $replacements[$original] = $domain['machine_name'];
          break;
        case 'name':
          $replacements[$original] = $sanitize ? check_plain($domain['sitename']) : $domain['sitename'];
          break;
        case 'path':
          if (!isset($domain['path'])) {
            $domain['path'] = domain_get_path($domain);
          }
          $replacements[$original] = $domain['path'];
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
        case 'hostname':
          $subdomain = $domain['subdomain'];
          $replacements[$original] = $sanitize ? check_plain($subdomain) : $subdomain;
          break;
      }
    }
  }

  // Node tokens.
  if ($type == 'node' && !empty($data['node']->nid)) {
    $node = $data['node'];
    $domain = domain_get_node_match($node->nid);
    if (empty($domain) || $domain == -1) {
      return $replacements;
    }
    // Loop through the tokens to not waste cycles.
    foreach ($tokens as $name => $original) {
      if ($name == 'domain') {
        $replacements[$original] = $sanitize ? check_plain($domain['sitename']) : $domain['sitename'];
      }
    }
    if ($domain_tokens = token_find_with_prefix($tokens, 'domain')) {
      $replacements += token_generate('domain', $domain_tokens, array('domain' => $domain), $options);
    }
  }

  // Current domain tokens.
  elseif ($type == 'current-domain') {
    $current_domain = domain_get_domain();
    $replacements += token_generate('domain', $tokens, array('domain' => $current_domain), $options);
  }

  // Default domain tokens.
  elseif ($type == 'default-domain') {
    $default_domain = domain_default(FALSE);
    $replacements += token_generate('domain', $tokens, array('domain' => $default_domain), $options);
  }

  return $replacements;
}
