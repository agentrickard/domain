<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait DomainAliasTestTrait {

  /**
   * Creates an alias.
   *
   * @param $values array
   *   An array of values to assign to the alias.
   */
  public function createDomainAlias($values) {
    // Replicate the logic for creating machine_name patterns.
    // @see ConfigBase::validate()
    $machine_name = strtolower(preg_replace('/[^a-z0-9_]/', '_', $values['pattern']));
    $values['id'] = str_replace(array('*', '.', ':'), '_', $machine_name);
    $alias = \Drupal::entityTypeManager()->getStorage('domain_alias')->create($values);
    $alias->save();
  }

}
