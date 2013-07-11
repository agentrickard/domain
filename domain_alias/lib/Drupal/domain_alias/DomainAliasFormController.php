<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasFormController.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Entity\EntityFormController;
use Drupal\domain\DomainInterface;
use Drupal\domain_alias\DomainAliasInterface;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $alias = $this->entity;

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $alias = $this->entity;
    $status = $alias->save();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    #$form_state['redirect'] = 'admin/structure/contact/manage/' . $this->entity->id() . '/delete';
  }

}
