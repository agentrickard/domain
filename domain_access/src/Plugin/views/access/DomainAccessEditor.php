<?php

namespace Drupal\domain_access\Plugin\views\access;

/**
 * Access plugin that provides domain-editing access control.
 *
 * @ViewsAccess(
 *   id = "domain_access_admin",
 *   title = @Translation("Domain Access: Administer domain editors"),
 *   help = @Translation("Access will be granted to domains on which the user may assign editors.")
 * )
 */
class DomainAccessEditor extends DomainAccessContent {

  /**
   * Sets the permission to use when checking access.
   *
   * @var string
   */
  protected $permission = 'assign domain editors';

  /**
   * Sets the permission to use when checking all access.
   *
   * @var string
   */
  protected $allPermission = 'assign editors to any domain';

}
