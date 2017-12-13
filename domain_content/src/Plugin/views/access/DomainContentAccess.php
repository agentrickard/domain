<?php

namespace Drupal\domain_content\Plugin\views\access;

use Symfony\Component\Routing\Route;
use Drupal\domain_access\Plugin\views\access\DomainAccessContent;

/**
 * Access plugin that provides domain-editing access control.
 *
 * These access controls extend those provided by Domain Access, merely adding
 * an additional permission specific to this module.
 *
 * @ViewsAccess(
 *   id = "domain_content_editor",
 *   title = @Translation("Domain Content: View domain-specific content"),
 *   help = @Translation("Access will be granted to domains on which the user may edit content.")
 * )
 */
class DomainContentAccess extends DomainAccessContent {

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    parent::alterRouteDefinition($route);
    $route->setRequirement('_permission', 'access domain content');
  }

}
