<?php

/**
 * @file
 * Definition of Drupal\domain\DomainRenderController.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Plugin\Core\Entity\EntityDisplay;

/**
 * Render controller for domain records.
 */
class DomainRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    // If we can get domain_field_extra_fields() working here, we may not even
    // need this override class and can do everything via formatters.
    parent::buildContent($entities, $displays, $view_mode, $langcode);
  }

}
