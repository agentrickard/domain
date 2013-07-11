<?php
/**
 * @file
 * Contains \Drupal\domain\Controller\DomainController.
 */

namespace Drupal\domain\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainManager;
use Drupal\domain\DomainInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

  /**
   * Returns the add form.
   */
  public function addDomain($inherit = FALSE) {
    $domain = domain_create($inherit);
    return entity_get_form($domain);
  }

  /**
   * Controller for handling Ajax operations from the overview page.
   *
   * @param \Drupal\domain\DomainInterface
   *   A domain record object.
   * @param $op
   *   The operation being performed.
   */
  public function ajaxOperation(DomainInterface $domain, $op = NULL) {
    // @todo CSRF tokens are validated in page callbacks rather than access
    //   callbacks, because access callbacks are also invoked during menu link
    //   generation. Add token support to routing: http://drupal.org/node/755584.
    $token = drupal_container()->get('request')->query->get('token');
    $allowed_actions = array('enable', 'disable', 'default');

    if (!in_array($op, $allowed_actions) || !isset($token) || !drupal_valid_token($token)) {
      throw new AccessDeniedHttpException();
    }

    $success = FALSE;
    switch($op) {
      case 'default':
        $domain->saveDefault();
        $verb = t('set as default');
        if ($domain->isDefault()) {
          $success = TRUE;
        }
        break;
      case 'enable':
        $domain->enable();
        $verb = t('has been enabled.');
        if ($domain->status) {
          $success = TRUE;
        }
        break;
      case 'disable':
        $domain->disable();
        $verb = t('has been disabled.');
        if (!$domain->status) {
          $success = TRUE;
        }
        break;
    }

    // @TODO: This no longer works in Drupal 8.
    // See https://drupal.org/node/2023445#comment-7638261
    if ($success) {
      drupal_set_message(t('Domain record !verb.', array('!verb' => $verb)));
    }

    // Return to the invoking page.
    $destination = drupal_get_destination();
    return new RedirectResponse($destination['destination']);
  }

}
