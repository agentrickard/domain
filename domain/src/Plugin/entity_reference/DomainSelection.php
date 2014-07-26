<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Type\selection\DomainSelection.
 */

namespace Drupal\domain\Plugin\entity_reference\selection;

use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

/**
 * Provides specific access control for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "domain_default",
 *   label = @Translation("Domain selection"),
 *   entity_types = {"domain"},
 *   group = "default",
 *   weight = 1
 * )
 */
class DomainSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Filter domains by the user's assignments.
    // @TODO: allow users to be assigned to domains.
    $account = Drupal::currentUser();
    if ($account->hasPermission('administer domains')) {
      return $query;
    }

    return $query;
  }
}
