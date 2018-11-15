<?php

/**
 * @file
 * Domain hook test module.
 */

use Drupal\Core\Url;
use Drupal\domain\DomainInterface;

/**
 * Implements hook_domain_load().
 */
function domain_test_domain_load(array $domains) {
  foreach ($domains as $domain) {
    $domain->addProperty('foo', 'bar');
  }
}

/**
 * Implements hook_domain_validate_alter().
 */
function domain_test_domain_validate_alter(&$error_list, $hostname) {
  // Deliberate test fail.
  if ($hostname == 'fail.example.com') {
    $error_list[] = 'Fail.example.com cannot be registered';
  }
}

/**
 * Implements hook_domain_request_alter().
 */
function domain_test_domain_request_alter(DomainInterface &$domain) {
  $domain->addProperty('foo1', 'bar1');
}

/**
 * Implements hook_domain_operations().
 */
function domain_test_domain_operations(DomainInterface $domain) {
  $operations = [];
  // Add aliases to the list.
  $id = $domain->id();
  $operations['domain_test'] = [
    'title' => t('Test'),
    'url' => Url::fromRoute('entity.domain.edit_form', ['domain' => $id]),
    'weight' => 80,
  ];
  return $operations;
}

/**
 * Implements hook_domain_references_alter().
 */
function domain_test_domain_references_alter($query, $account, $context) {
  if ($context['entity_type'] == 'node') {
    $test = 'Test string';
    $query->addMetadata('domain_test', $test);
  }
}
