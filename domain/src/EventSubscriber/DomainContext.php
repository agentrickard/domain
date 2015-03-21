<?php

/**
 * @file
 * Contains \Drupal\domain\Event\DomainContext.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal\block\Event\BlockContextEvent;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current user as a context.
 */
class DomainContext extends BlockContextSubscriberBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $loader;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainCreator object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockActiveContext(BlockContextEvent $event) {
    $current_domain = $this->userStorage->load($this->account->id());

    $context = new Context(new ContextDefinition('entity:user', $this->t('Current user')));
    $context->setContextValue($current_user);
    $event->setContext('user.current_user', $context);
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockAdministrativeContext(BlockContextEvent $event) {
    $this->onBlockActiveContext($event);
  }

}


