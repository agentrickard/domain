<?php

namespace Drupal\domain_config_ui\Controller;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for AJAX callbacks for domain actions.
 */
class DomainConfigUIController {

  use StringTranslationTrait;

  /**
   * Handles AJAX operations from the overview form.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain record object.
   * @param string $op
   *   The operation being performed, either 'default' to make the domain record
   *   the default, 'enable' to enable the domain record, or 'disable' to
   *   disable the domain record.
   *
   *   Note: The delete action is handled by the entity form system.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to redirect back to the domain record list.
   *   Supported by the UrlGeneratorTrait.
   *
   * @see \Drupal\domain\DomainListBuilder
   */
  public function ajaxOperation($route_name, $op) {
    $success = FALSE;
    $url = Url::fromRoute($route_name);
    // Get current module settings.
    $config = \Drupal::configFactory()->getEditable('domain_config_ui.settings');
    $path_pages = $config->get('path_pages');

    // Check to see if we already registered this form.
    $exists = \Drupal::service('path.matcher')->matchPath($url->toString(), $path_pages);
    if (!$exists && !$url->isExternal() && $url->access()) {
      switch ($op) {
        case 'enable':
          // TODO: reverse logic if negate is turned on.
          $config->set('path_pages', $path_pages . "\r\n" . $url->toString())
            ->save();
          $message = $this->t('Form added to domain configuration interface.');
          $success = TRUE;
          break;
        case 'disable':
          break;
      }

    }
    // Set a message.
    if ($success) {
      \Drupal::messenger()->addMessage($message);
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The operation failed.'));
    }

    // Return to the invoking page.
    return new RedirectResponse($url->toString(), 302);
  }

}
