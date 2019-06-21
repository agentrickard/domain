<?php

/**
 * @file
 * Hook implementations for this module.
 */

use Drupal\domain\DomainInterface;

/**
 * Implements hook_domain_request_alter().
 */
function domain_config_test_domain_request_alter(DomainInterface $domain) {
  $domain->addProperty('config_test', 'aye');
}

/**
 * Implements hook_page_attachments_alter().
 */
function domain_config_test_page_attachments_alter(array &$attachments) {
  /** @var \Drupal\domain\DomainNegotiatorInterface $Negotiator */
  $negotiator = \Drupal::service('domain.negotiator');
  $domain = $negotiator->getActiveDomain();
  if (!empty($domain) && $domain->get('config_test') == 'aye') {
    $attachments['#attached']['http_header'][] = [
      'X-Domain-Config-Test-page-attachments-hook',
      'invoked',
    ];
  }
}
