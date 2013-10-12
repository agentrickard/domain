<?php
/**
 * @file
 * Definition of \Drupal\domain_config\EventSubscriber\DomainConfigSubscriber.
 */

namespace Drupal\domain_config\EventSubscriber;

use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Config\ConfigEvent;
use Drupal\domain\DomainManagerInterface;
use Drupal\domain\DomainInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Domain Config helper
 */
class DomainConfigSubscriber implements EventSubscriberInterface {

  const CONFIG_CONTEXT = 'domain_config.domain';

  /**
   * The domain manager.
   *
   * @var \Drupal\domain\DomainManagerInterface
   */
  protected $domainManager;

  /**
   * The default configuration context.
   *
   * @var \Drupal\Core\Config\Context\ConfigContext
   */
  protected $defaultConfigContext;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\domain\DomainManagerInterface $domain_manager
   *   The domain manager service.
   * @param \Drupal\Core\Config\Context\ContextInterface $config_context
   *   The config context service.
   */
  public function __construct(DomainManagerInterface $domain_manager, ContextInterface $config_context) {
    $this->domainManager = $domain_manager;
    $this->defaultConfigContext = $config_context;
  }

  /**
   * Initialize configuration context with domain.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configContext(ConfigEvent $event) {
    $context = $event->getContext();

    // Add active domain to default context.
    $context->set(self::CONFIG_CONTEXT, $this->domainManager->getActiveDomain());
  }

  /**
   * Override configuration values with domain-specific data.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configLoad(ConfigEvent $event) {
    $context = $event->getContext();

    if ($domain = $context->get(self::CONFIG_CONTEXT)) {
      $config = $event->getConfig();
      $config_name = $this->getDomainConfigName($config->getName(), $domain);
      // Check to see if the config storage has an appropriately named file
      // containing override data.
      if ($override = $event->getConfig()->getStorage()->read($config_name)) {
        $config->setOverride($override);
      }
    }
  }

  /**
   * Sets the active domain on the default configuration context.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Kernel event to respond to.
   */
  public function onKernelRequestDomain(GetResponseEvent $event) {
    $this->defaultConfigContext->init();
  }

  /**
   * Get configuration name for this hostname.
   *
   * It will be the same name with a prefix depending on domain:
   * domain.config.DOMAIN.MACHINE_NAME
   *
   * @param string $name
   *   The name of the config object.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object.
   *
   * @return string
   *   The domain-specific config name.
   */
  public function getDomainConfigName($name, DomainInterface $domain) {
    return 'domain.config.' . $domain->machine_name->value . '.' . $name;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.context'][] = array('configContext', 20);
    $events['config.load'][] = array('configLoad', 20);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDomain', 20);
    return $events;
  }
}
