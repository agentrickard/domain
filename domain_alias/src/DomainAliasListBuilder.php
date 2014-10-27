<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Form\DomainAliasListBuilder.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * User interface for the domain alias overview screen.
 */
class DomainAliasListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $operations['delete']['href'] = 'admin/structure/domain/alias/delete/' . $entity->id();
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Pattern');
    $header['redirect'] = $this->t('Redirect');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $redirect = $entity->getRedirect();
    $row['redirect'] = empty($redirect) ? $this->t('None') : $redirect;
    return $row += parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => $this->t('No aliases have been created for this domain.'),
    );
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['#rows'][$entity->id()] = $row;
      }
    }
    return $build;
  }
}
