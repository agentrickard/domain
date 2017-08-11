<?php

namespace Drupal\domain_alias\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\domain\DomainInterface;

/**
 * Returns responses for Domain Alias module routes.
 */
class DomainAliasController extends ControllerBase {

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

    // Create the stub alias with reference to the parent domain.
    $alias = $this->entityTypeManager()->getStorage('domain_alias')->create($values);

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
    $list = $this->entityTypeManager()->getListBuilder('domain_alias');
    $list->setDomain($domain);
    return $list->render();
  }

}
