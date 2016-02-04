<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Controller\DomainAliasController.
 */

namespace Drupal\domain_alias\Controller;

use Drupal\domain\DomainInterface;
use Drupal\domain\Controller\DomainControllerBase;
use Drupal\domain_alias\DomainAliasListBuilder;

/**
 * Returns responses for Domain Alias module routes.
 */
class DomainAliasController extends DomainControllerBase {

  /**
   * Provides the domain alias submission form.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   An domain record entity.
   *
   * @return array
   *   Returns the domain alias submission form.
   */
  public function addAlias(DomainInterface $domain) {
    // The entire purpose of this controller is to add the values from
    // the parent domain entity.
    $values['domain_id'] = $domain->id();
    // @TODO: ensure that this value is present in all cases.
    $alias = \Drupal::entityManager()->getStorage('domain_alias')->create($values);

    return $this->entityFormBuilder()->getForm($alias);
  }

  /**
   * Provides the listing page for aliases.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   An domain record entity.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing(DomainInterface $domain) {
    $type = \Drupal::entityTypeManager()->getDefinition('domain_alias');
    $storage = \Drupal::entityTypeManager()->getStorage('domain_alias');
    $list = new DomainAliasListBuilder($type, $storage);
    $list->setDomain($domain);
    return $list->render();
  }

}
