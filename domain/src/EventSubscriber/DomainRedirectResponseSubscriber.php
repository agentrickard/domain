<?php

namespace Drupal\domain\EventSubscriber;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Core\Routing\LocalRedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Allows manipulation of the response object when performing a redirect.
 */
class DomainRedirectResponseSubscriber extends RedirectResponseSubscriber {

  /**
   * Allows manipulation of the response object when performing a redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function checkRedirectUrl(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $request = $event->getRequest();
      // Let the 'destination' query parameter override the redirect target.
      // If $response is already a SecuredRedirectResponse, it might reject the
      // new target as invalid, in which case proceed with the old target.
      $destination = $request->query->get('destination');
      if ($destination) {
        // The 'Location' HTTP header must always be absolute.
        $destination = $this->getDestinationAsAbsoluteUrl($destination, $request->getSchemeAndHttpHost());
        try {
          $response->setTargetUrl($destination);
        }
        catch (\InvalidArgumentException $e) {

        }
      }
    }
    // Domain overrides core's functionality which causes an exception on
    // external redirects. This is because domain access induces heavy conflict with
    // this core functionality in some scenarios, especially with multilingual content.
    if (($response->getStatusCode() == 301 || $response->getStatusCode() == 302) && !($response instanceof SecuredRedirectResponse)) {
      // SecuredRedirectResponse is an abstract class that requires a
      // concrete implementation. Default to TrustedRedirectResponse.
      $safe_response = new TrustedRedirectResponse($response->getTargetUrl(), $response->getStatusCode());
      $safe_response->setRequestContext($this->requestContext);
      $event->setResponse($safe_response);
    }
  }

}
