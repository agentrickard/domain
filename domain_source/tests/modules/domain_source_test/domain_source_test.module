<?php

/**
 * Implements hook_domain_source_path_alter()
 */
function domain_source_test_domain_source_alter(&$source, $path, $options) {
  // Always make our test REST links go to the primary domain.
  var_dump($path);
  $parts = explode('/', $path);
  if (isset($parts[1]) && $parts[1] == 'domain-format-test') {
    $source = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
  }
}
