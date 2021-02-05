<?php

namespace Drupal\domain;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the router.route_provider service.
 *
 * Point to our customized one and adds url.site to the
 * required_cache_contexts renderer configuration.
 *
 * @see https://www.drupal.org/node/2662196#comment-10838164
 */
class DomainServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add the site context to the render cache.
    if ($container->hasParameter('renderer.config')) {
      $renderer_config = $container->getParameter('renderer.config');

      if (!in_array('url.site', $renderer_config['required_cache_contexts'])) {
        $renderer_config['required_cache_contexts'][] = 'url.site';
      }

      $container->setParameter('renderer.config', $renderer_config);
    }
  }

}
