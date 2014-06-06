<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Action\DefaultDomain.
 */

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\domain\DomainInterface;

/**
 * Sets the domain is_default property to 1.
 *
 * @Action(
 *   id = "domain_default_action",
 *   label = @Translation("Set default domain record"),
 *   type = "domain"
 * )
 */
class DefaultDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->saveDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceOf DomainInterface) {
        $object->saveDefault();
      }
    }
  }

}
