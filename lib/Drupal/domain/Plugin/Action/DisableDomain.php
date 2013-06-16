<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Action\DisableDomain.
 */

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ActionBase;
use Drupal\domain\Plugin\Core\Entity\Domain;

/**
 * Sets the domain status property to 0.
 *
 * @Action(
 *   id = "domain_disable_action",
 *   label = @Translation("Disable domain record"),
 *   type = "domain"
 * )
 */
class DisableDomain extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(Domain $domain = NULL) {
    $domain->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceOf Domain) {
        $object->disable();
      }
    }
  }

}
