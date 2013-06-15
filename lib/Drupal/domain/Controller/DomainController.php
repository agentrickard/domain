<?php
/**
 * @file
 * Contains \Drupal\domain\Controller\DomainController.
 */

namespace Drupal\domain\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainManager;

/**
 * Controller routines for domain routes.
 */
class DomainController implements ControllerInterface {

  /**
   * Domain Manager Service.
   *
   * @var \Drupal\domain\DomainManager
   */
  protected $domainManager;

   /**
   * Injects DomainManager Service.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('domain.manager'));
  }

  /**
   * Constructs a DomainController object.
   */
  public function __construct(DomainManager $domainManager) {
    $this->domainManager = $domainManager;
  }

  /**
   * Returns the admin form.
   */
  public function adminOverview() {
    $domains = domain_load_multiple();
    module_load_include('inc', 'domain', 'domain.admin');
    return drupal_get_form('domain_overview_form', $domains);
  }

}
