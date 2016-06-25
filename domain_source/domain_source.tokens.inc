<?php

/**
 * @file
 * Token callbacks for the domain_source module.
 */

/**
 * Implements hook_token_info().
 * Provide information about our source domain token.
 *
 * @see http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_token_info/7
 * @return array
 *   An associative array of available tokens and token types.
 */
function domain_source_token_info() {

  // Node tokens.
  $info['tokens']['node']['source-domain-node-url'] = array(
    'name' => t('Node URL with base source domain url.'),
    'description' => t("Node URL with base source domain url. Useful for canonical link."),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 * Provide replacement values for placeholder tokens.
 *
 * @see http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_tokens/7
 * @param string $type
 *   The machine-readable name of the type (group) of token being replaced, such
 *   as 'node', 'user', or another type defined by a hook_token_info()
 *   implementation.
 * @param array $tokens
 *   An array of tokens to be replaced. The keys are the machine-readable token
 *   names, and the values are the raw [type:token] strings that appeared in the
 *   original text.
 * @param array $data (optional)
 *   An associative array of data objects to be used when generating replacement
 *   values, as supplied in the $data parameter to token_replace().
 * @param array $options (optional)
 *   An associative array of options for token replacement; see token_replace()
 *   for possible values.
 * @return array
 *   An associative array of replacement values, keyed by the raw [type:token]
 *   strings from the original text.
 */
function domain_source_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  // Node tokens.
  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'source-domain-node-url':
          // Get the query string parameters. If none set, set to NULL
          $query_string = drupal_get_query_parameters();
          if (empty($query_string)) {
            $query_string = NULL;
          }
          $options = array(
            'query' => $query_string,
            'absolute' => TRUE,
          );
          if (!empty($node->domain_source)) {
            $domain = domain_lookup($node->domain_source);
            $options['base_url'] = rtrim($domain['path'], '/');
          }
          $replacements[$original] = url('node/' . $node->nid, $options);
          break;
      }
    }

  }
  return $replacements;
}
