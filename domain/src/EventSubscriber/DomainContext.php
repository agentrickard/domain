<?php

/**
 * @file
 * Contains \Drupal\domain\Event\DomainContext.
 */

namespace Drupal\domain\EventSubscriber;

use Drupal\block\Event\BlockContextEvent;
use Drupal\block\EventSubscriber\BlockContextSubscriberBase;
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
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainContext object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockActiveContext(BlockContextEvent $event) {
    $current_domain = $this->negotiator->getActiveDomain();
    $context = new Context(new ContextDefinition('entity:domain', $this->t('Active domain')));
    $context->setContextValue($current_domain);
    $event->setContext('domain.current_domain', $context);
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockAdministrativeContext(BlockContextEvent $event) {
    $this->onBlockActiveContext($event);
  }

}


