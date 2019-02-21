<?php

namespace Drupal\domain_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Class AccessMethodBase.
 *
 * @package Drupal\domain_access
 */
abstract class AccessMethodBase implements AccessMethodInterface {

  /**
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $domainAccessManager;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AccessMethodBase constructor.
   *
   * @param \Drupal\domain_access\DomainAccessManagerInterface $domainAccessManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(DomainAccessManagerInterface $domainAccessManager, DomainNegotiatorInterface $domainNegotiator, EntityTypeManagerInterface $entityTypeManager) {
    $this->domainAccessManager = $domainAccessManager;
    $this->domainNegotiator = $domainNegotiator;
    $this->entityTypeManager = $entityTypeManager;
  }

}