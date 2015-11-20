<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\EntityReferenceSelection\DomainSelection.
 */

namespace Drupal\domain\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\user\Entity\User;

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
    // Let administrators do anything.
    if ($this->currentUser->hasPermission('administer domains')) {
      return $query;
    }
    // Can this user access inactive domains?
    if (!$this->currentUser->hasPermission('access inactive domains')) {
      $query->condition('status', 1);
    }
    // Filter domains by the user's assignments, which are controlled by other
    // modules.
    $account = User::load($this->currentUser->id());
    $this->moduleHandler->alter('domain_references', $query, $account);
    return $query;
  }
}
