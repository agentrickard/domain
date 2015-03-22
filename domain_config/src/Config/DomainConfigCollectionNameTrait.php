<?php

/**
 * @file
 * Contains \Drupal\domain_config\Config\DomainConfigCollectionNameTrait.
 */

namespace Drupal\domain_config\Config;

use Drupal\Component\Utility\String;

/**
 * Provides a common trait for working with domain override collection names.
 */
trait DomainConfigCollectionNameTrait {

  /**
   * Creates a configuration collection name based on a domain id.
   *
   * @param string $id
   *   The domain id.
   *
   * @return string
   *   The configuration collection name for a domain id.
   */
  protected function createConfigCollectionName($id) {
    return 'domain.' . $id;
  }

  /**
   * Converts a configuration collection name to a domain id.
   *
   * @param string $collection
   *   The configuration collection name.
   *
   * @return string
   *   The domain id of the collection.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if the provided collection name is not in the format
   *   "domain.ID".
   *
   * @see self::createConfigCollectionName()
   */
  protected function getLangidFromCollectionName($collection) {
    preg_match('/^domain\.(.*)$/', $collection, $matches);
    if (!isset($matches[1])) {
      throw new \InvalidArgumentException(String::format('!collection is not a valid domain override collection', array('!collection' => $collection)));
    }
    return $matches[1];
  }

}
