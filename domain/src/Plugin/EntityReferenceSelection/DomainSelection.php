<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\EntityReferenceSelection\DomainSelection.
 */

namespace Drupal\domain\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:domain",
 *   label = @Translation("Domain selection"),
 *   entity_types = {"domain"},
 *   group = "default",
 *   weight = 1
 * )
 */
class DomainSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Filter domains by the user's assignments.
    // @TODO: allow users to be assigned to domains.
    $account = \Drupal::currentUser();
    if ($account->hasPermission('administer domains')) {
      return $query;
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    #if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
    #  $entities = array_filter($entities, function ($node) {
    #    /** @var \Drupal\node\NodeInterface $node */
    #    return $node->isPublished();
    #  });
    #}
    return $entities;
  }
}
