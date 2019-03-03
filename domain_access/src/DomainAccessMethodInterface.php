<?php

namespace Drupal\domain_access;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Interface AccessMethodInterface.
 *
 * @package Drupal\domain_access
 */
interface DomainAccessMethodInterface {

  /**
   * Build the grants avaible for passed user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account of the user to generate the accounts for.
   * @param $op
   *   The operation to generate the grants for.
   *
   * @return array
   */
  public function nodeAccessGrants(AccountInterface $account, $op);

  /**
   * Build node access records.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node for the records to be created for.
   *
   * @return array
   */
  public function nodeAccessRecords(NodeInterface $node);

}
