<?php

/**
 * @file
 * Contains \Drupal\domain\DomainViewBuilder.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a Domain view builder.
 */
class DomainViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    // @TODO: This is a stopgap. The entire entity list should be returned in
    // one function.
    $build = array();
    uasort($entities, 'domain_list_sort');
    foreach ($entities as $entity_id => $entity) {
      // @TODO: set this properly from variables.
      $build[$entity_id] = array(
        '#markup' => l($entity->name, $entity->url),
        '#entity' => $entity,
      );
    }
    return $build;
  }

}
