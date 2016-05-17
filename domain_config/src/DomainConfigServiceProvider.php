<?php

namespace Drupal\domain_config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\domain_config\Routing\DomainRouteProvider;

/**
 * Overrides the router.route_provider service.
 *
 * Point to our customized one and adds url.site to the
 * required_cache_contexts renderer configuration.
 *
 * @see https://www.drupal.org/node/2662196#comment-10838164
 */
class DomainConfigServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('router.route_provider');
    $definition->setClass(DomainRouteProvider::class);

    if ($container->hasParameter('renderer.config')) {
      $renderer_config = $container->getParameter('renderer.config');

      if (!in_array('url.site', $renderer_config['required_cache_contexts'])) {
        $renderer_config['required_cache_contexts'][] = 'url.site';
      }

      $container->setParameter('renderer.config', $renderer_config);
    }
  }

}
