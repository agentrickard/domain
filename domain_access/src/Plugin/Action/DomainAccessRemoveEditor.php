<?php

namespace Drupal\domain_access\Plugin\Action;

use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Removes an editor from a domain.
 *
 * @Action(
 *   id = "domain_access_remove_editor_action",
 *   label = @Translation("Remove domain from editors"),
 *   type = "user"
 * )
 */
class DomainAccessRemoveEditor extends DomainAccessRemove {
  // This class does the same action to a different type of entity.
}
