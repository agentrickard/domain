<?php

namespace Drupal\domain\Plugin\LanguageNegotiation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\language\Annotation\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via a domain record.
 *
 * @LanguageNegotiation(
 *   id = "domain",
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT},
 *   weight = 0,
 *   name = @Translation("Domain record"),
 *   description = @Translation("Language specified for a registered Domain or its aliases"),
 *   config_route_name = "domain.language_negotiation"
 * )
 */
class Domain extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface, LanguageSwitcherInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'domain';

  /**
   * Domain Negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  private $domainNegotiator;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageNegotiationUserAdmin instance.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   Domain Negotiator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language Manager.
   */
  public function __construct(DomainNegotiatorInterface $domainNegotiator, ConfigFactoryInterface $configFactory, LanguageManagerInterface $languageManager) {
    $this->domainNegotiator = $domainNegotiator;
    $this->configFactory = $configFactory;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('domain.negotiator'),
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($domain = $this->domainNegotiator->getActiveDomain()) {
      $values = $this->configFactory->get('domain.language_negotiation')->get('domain_language');
      $langcode = $values[$domain->id()] ?: NULL;
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = [];
    // The links array expects a 1:1 relationship between language and domain.
    // That is not the case for us, so we recommend using the provided
    // Domain Navigation block instead.
    return $links;
  }

}
