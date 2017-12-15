<?php

namespace Drupal\domain_content\Plugin\views\access;

use Symfony\Component\Routing\Route;
use Drupal\domain_access\Plugin\views\access\DomainAccessEditor;

/**
 * Access plugin that provides domain assignment access control.
 *
 * These access controls extend those provided by Domain Access, merely adding
 * an additional permission specific to this module.
 *
 * @ViewsAccess(
 *   id = "domain_content_admin",
 *   title = @Translation("Domain Content: View domain-specific editors"),
 *   help = @Translation("Access will be granted to domains on which the user may assign editors.")
 * )
 */
class DomainEditorAccess extends DomainAccessEditor {

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    parent::alterRouteDefinition($route);
    $route->setRequirement('_permission', 'access domain content editors');
  }

}
