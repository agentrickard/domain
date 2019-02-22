<?php

namespace Drupal\domain_access\AccessMethod;

use Drupal\Core\Session\AccountInterface;
use Drupal\domain_access\DomainAccessMethodInterface;
use Drupal\node\NodeInterface;

/**
 * Class Null.
 *
 * @package Drupal\domain_access
 */
class NoRecords implements DomainAccessMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function Grants(AccountInterface $account, $op) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function Records(NodeInterface $node) {
    return [];
  }

}
