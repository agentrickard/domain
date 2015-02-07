<?php
/**
 * @file
 * Contains \Drupal\domain\Controller\DomainController.
 */

namespace Drupal\domain\Controller;

use Drupal\domain\DomainInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller routines for domain routes.
 */
class DomainController {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Handles AJAX operations from the overview form.
   *
   * @param \Drupal\domain\DomainInterface
   *   A domain record object.
   * @param $op
   *   The operation being performed.
   *
   * @see \Drupal\domain\DomainListBuilder
   */
  public function ajaxOperation(DomainInterface $domain, $op = NULL) {
    $success = FALSE;
    switch($op) {
      case 'default':
        $domain->saveDefault();
        $verb = $this->t('set as default');
        if ($domain->isDefault()) {
          $success = TRUE;
        }
        break;
      case 'enable':
        $domain->enable();
        $verb = $this->t('has been enabled.');
        if ($domain->status()) {
          $success = TRUE;
        }
        break;
      case 'disable':
        $domain->disable();
        $verb = $this->t('has been disabled.');
        if (!$domain->status()) {
          $success = TRUE;
        }
        break;
    }

    // Set a message.
    if ($success) {
      drupal_set_message($this->t('Domain record !verb.', array('!verb' => $verb)));
    }

    // Return to the invoking page.
    return new RedirectResponse($this->url('domain.admin'));
  }

}
