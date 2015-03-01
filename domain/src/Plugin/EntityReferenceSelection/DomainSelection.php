<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\EntityReferenceSelection\DomainSelection.
 */

namespace Drupal\domain\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:domain",
 *   label = @Translation("Domain record selection"),
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
    // @TODO: Filter domains by the user's assignments.
    return $query;
  }

  /**
   * {@inheritdoc}
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

  }
  */

  /**
   * {@inheritdoc}
  public function entityQueryAlter(SelectInterface $query) {

  }
   */

  /**
   * {@inheritdoc}

  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {

  }
   */
}
