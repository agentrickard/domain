<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasFormController.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $alias = $this->entity;
    dpm($alias);
    dpm($form);
    return parent::form($form, $form_state, $alias);
  }
}
