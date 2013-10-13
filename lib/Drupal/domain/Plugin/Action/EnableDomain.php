<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Action\EnableDomain.
 */

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\domain\DomainInterface;

/**
 * Sets the domain status property to 1.
 *
 * @Action(
 *   id = "domain_enable_action",
 *   label = @Translation("Enable domain record"),
 *   type = "domain"
 * )
 */
class EnableDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(DomainInterface $domain = NULL) {
    $domain->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceOf DomainInterface) {
        $object->enable();
      }
    }
  }

}
