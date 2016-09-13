<?php

/**
 * @file
 * API documentation file for Domain module.
 */

/**
 * Notifies other modules that we are loading a domain record from the database.
 *
 * When using this hook, you should invoke the namespace with:
 *
 * use Drupal\domain\DomainInterface;
 *
 * @param array $domains
 *   An array of $domain record objects.
 */
function hook_domain_load(array $domains) {
  // Add a variable to the $domain.
  foreach ($domains as $domain) {
    $domain->addProperty('myvar', 'mydomainvar');
  }
}

/**
 * Allows modules to modify the inbound domain request.
 *
 * When using this hook, first check $domain->getMatchType(), which returns a
 * numeric constant indicating the type of match derived by the caller or by
 * earlier returns of this hook (such as domain_alias_request_alter()).
 * Use this value to determine if the request needs to be overridden. Valid
 * types are DomainNegotiator::DOMAIN_MATCH_NONE,
 * DomainNegotiator::DOMAIN_MATCH_EXACT, DomainNegotiator::DOMAIN_MATCH_ALIAS.
 *
 * To issue a redirect, as in the case of Domain Alias, set a redirect
 * property to a valid response code (301 or 302).
 *
 * @param \Drupal\domain\DomainInterface $domain
 *   A domain object defined by Drupal\domain\DomainInterface.
 */
function hook_domain_request_alter(\Drupal\domain\DomainInterface &$domain) {
  // Add a special case to the example domain.
  if ($domain->getMatchType() == \Drupal\domain\DomainNegotiator::DOMAIN_MATCH_EXACT && $domain->id() == 'example_com') {
    // Do something here.
    $domain->addProperty('foo', 'Bar');
  }
}

/**
 * Adds administrative operations for the domain overview form.
 *
 * @param \Drupal\domain\DomainInterface $domain
 *   A domain record object.
 *
 * @return array
 *   An array of operations which uses a unique string key and requires the
 *   elements 'title' and 'url'; the 'query' value is optional, and used
 *   for link-actions with tokens
 */
function hook_domain_operations(\Drupal\domain\DomainInterface $domain) {
  // Add aliases to the list.
  $id = $domain->id();
  $operations['domain_alias'] = array(
    'title' => t('Aliases'),
    'url' => \Drupal\Core\Url::fromRoute('domain_alias.admin', array('domain' => $id)),
    'weight' => 60,
  );
  return $operations;
}

/**
 * Alter the validation step of a domain record.
 *
 * This hook allows modules to change or extend how domain validation
 * happens. Most useful for international domains or other special cases
 * where a site wants to restrict domain creation is some manner.
 *
 * NOTE: This does not apply to Domain Alias records.
 *
 * @param array &$error_list
 *   The list of current validation errors. Modify this value by reference.
 *   If you return an empty array or NULL, the domain is considered valid.
 * @param string $hostname
 *   The HTTP_HOST string value being validated, such as one.example.com.
 *   Note that this is checked for uniqueness separately. This value is not
 *   modifiable.
 *
 * No return value. Modify $error_list by reference. Return an empty array
 * or NULL to validate this domain.
 *
 * @see domain_valid_domain()
 */
function hook_domain_validate_alter(&$error_list, $hostname) {
  // Only allow TLDs to be .org for our site.
  if (substr($hostname, -4) != '.org') {
    $error_list[] = t('Only .org domains may be registered.');
  }
}

/**
 * Alter the list of domains that may be referenced.
 *
 * Note that this hook does not fire for users with the 'administer domains'
 * permission.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   An entity query prepared by DomainSelection::buildEntityQuery().
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account of the user viewing the reference list.
 * @param array $context
 *   A keyed array passing two items:
 *   - entity_type The type of entity (e.g. node, user) that requested the list.
 *   - bundle The entity subtype (e.g. 'article' or 'page').
 *
 * No return value. Modify the $query object via methods.
 */
function hook_domain_references_alter($query, $account, $context) {
  // Remove the default domain from non-admins when editing nodes.
  if ($context['entity_type'] == 'node' && !$account->hasPermission('edit assigned domains')) {
    $default = \Drupal::service('domain.loader')->loadDefaultId();
    $query->condition('id', $default, '<>');
  }
}
