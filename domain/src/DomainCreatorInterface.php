<?php

/**
 * @file
 * Definition of Drupal\domain\DomainCreatorInterface.
 */

namespace Drupal\domain;

use Drupal\domain\DomainInterface;

/**
 * Handles the creation of new domain records.
 */
interface DomainCreatorInterface {

  public function createDomain(array $values = array(), $inherit = FALSE);
  public function createNextId();
  public function createHostname();
  public function createMachineName($hostname);

}
