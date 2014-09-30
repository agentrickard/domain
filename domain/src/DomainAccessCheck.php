<?php

/**
 * @file
 * Defines \Drupal\domain\src\DoaminAccessCheck.
 */

namespace 'Drupal\domain';

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

class DomainAccessCheck {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  public function __construct(AccessManagerInterface $access_manager, AccountInterface $account, ModuleHandlerInterface $module_handler) {
    $this->accessManager = $access_manager;
    $this->moduleHandler = $module_handler;
    $this->account = $account;
    #$this->accessManager->addCheckService('domain.check', 'access', array(), TRUE);
  }

  public function access() {
    dpm('foo');
  }
}
