<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Assigns an editor to a domain.
 *
 * @Action(
 *   id = "domain_access_add_editor_action",
 *   label = @Translation("Add domain to editors"),
 *   type = "user"
 * )
 */
class DomainAccessAddEditor extends DomainAccessAdd {
  // This class does the same action to a different type of entity.
}
