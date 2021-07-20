<?php

namespace Drupal\domain\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainStorageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

/**
 * Configure the Domain language negotiation method for this site.
 */
class DomainLanguageNegotiationForm extends ConfigFormBase {

  /**
   * The domain storage manager.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new NegotiationUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);
    $this->domainStorage = $entity_type_manager->getStorage('domain');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_language_negotiation_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain.language_negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('domain.language_negotiation');
    $domains = $this->domainStorage->loadMultipleSorted();

    $languages = $this->languageManager->getLanguages();
    $options = [];
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $header['label'] = $this->t('Domain');
    $header['base_url'] = $this->t('Base url');
    $header['language'] = $this->t('Language');

    $form['domain_list'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($domains as $domain) {
      $row = [];
      $row['label'] = ['#markup' => $domain->label()];
      $form[$domain->id()] = $row;
    }

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save selected format (prefix or domain).
    $this->config('language.negotiation')
      ->set('url.source', $form_state->getValue('language_negotiation_url_part'))
      // Save new domain and prefix values.
      ->set('url.prefixes', $form_state->getValue('prefix'))
      ->set('url.domains', $form_state->getValue('domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
