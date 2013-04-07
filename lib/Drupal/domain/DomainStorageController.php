<?php

/**
 * @file
 * Definition of DomainStorageController.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageController;

/**
 * Defines a controller class for domain records.
 */
class DomainStorageController extends DatabaseStorageController {

  /**
   * Sets the default domain properly.
   */
  protected function preSave(EntityInterface $entity) {
    if (!empty($entity->is_default)) {
      // Swap the current default.
      if ($default = domain_default()) {
        $default->is_default = 0;
        $default->save();
      }
    }
  }

}
