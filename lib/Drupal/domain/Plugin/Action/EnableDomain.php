<?php

/**
 * @file
 * Contains \Drupal\domain\Plugin\Action\EnableDomain.
 */

namespace Drupal\domain\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ActionBase;
use Drupal\domain\Plugin\Core\Entity\Domain;

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
  public function execute(Domain $domain = NULL) {
    $domain->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    foreach ($objects as $object) {
      if ($object instanceOf Domain) {
        $object->enable();
      }
    }
  }

}
