<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Form\DomainAliasListController.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * User interface for the domain alias overview screen.
 */
class DomainAliasListController extends ConfigEntityListController {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Pattern');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = check_plain($this->getLabel($entity));
    $row += parent::buildRow($entity);
    return $row;
  }

}
