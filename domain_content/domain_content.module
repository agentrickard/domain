<?php

/**
 * @file
 * Hook implementations for this module.
 */

use Drupal\domain\DomainInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;

/**
 * Implements hook_domain_operations().
 */
function domain_content_domain_operations(DomainInterface $domain, AccountInterface $account) {
  $operations = [];

  // Advanced grants for edit/delete require permissions.
  $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
  $allowed = \Drupal::service('domain_access.manager')->getAccessValues($user);
  $id = $domain->id();
  if ($account->hasPermission('publish to any domain') || ($account->hasPermission('publish to any assigned domain') && isset($allowed[$domain->id()]))) {
    $operations['domain_content'] = [
      'title' => t('Content'),
      'url' => Url::fromUri("internal:/admin/content/domain-content/$id"),
      // Core operations start at 0 and increment by 10.
      'weight' => 120,
    ];
  }
  if ($account->hasPermission('assign editors to any domain') || ($account->hasPermission('assign domain editors') && isset($allowed[$domain->id()]))) {
    $operations['domain_users'] = [
      'title' => t('Editors'),
      'url' => Url::fromUri("internal:/admin/content/domain-editors/$id"),
      // Core operations start at 0 and increment by 10.
      'weight' => 120,
    ];
  }

  return $operations;
}
