<?php

/**
 * @file
 * Contains \Drupal\domain\DomainViewBuilder.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a Domain view builder.
 */
class DomainViewBuilder implements EntityViewBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

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

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) { }

}
