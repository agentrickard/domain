<?php

namespace Drupal\domain_config_ui\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainElementManagerInterface;
use Drupal\domain_config_ui\DomainConfigUIManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SwitchForm.
 */
class SwitchForm extends FormBase {

  /**
   * Constructs a new DevelGenerateForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\domain_config_ui\DomainConfigUIManager $domain_config_ui_manager
   *   The domain config UI manager.
   * @param \Drupal\domain\DomainElementManagerInterface $domain_element_manager
   *   The domain field element manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, DomainConfigUIManager $domain_config_ui_manager, DomainElementManagerInterface $domain_element_manager) {
    $this->domainConfigUiManager = $domain_config_ui_manager;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $this->entityTypeManager->getStorage('domain');
    $this->domainElementManager = $domain_element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('domain_config_ui.manager'),
      $container->get('domain.element_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_config_ui_switch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only allow access to domain administrators.
    $form['#access'] = $this->canUseDomainConfig();
    $form = $this->addSwitchFields($form, $form_state);
    return $form;
  }

  /**
   * Determines is a user may access the domain-sensitive form.
   */
  public function canUseDomainConfig() {
    if ($this->currentUser()->hasPermission('administer domains')) {
      return TRUE;
    }
    $account = $this->currentUser();
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $user_domains = $this->domainElementManager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    return (!empty($user_domains) && $this->currentUser()->hasPermission('use domain config ui'));
  }

  /**
   * Helper to add switch fields to form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   */
  public function addSwitchFields(array $form, FormStateInterface $form_state) {
    // Create fieldset to group domain fields.
    $form['domain_config_ui'] = [
      '#type' => 'fieldset',
      '#title' => 'Domain Configuration',
      '#weight' => -10,
    ];

    // Add domain switch select field.
    if ($selected_domain_id = $this->domainConfigUiManager->getSelectedDomainId()) {
      $selected_domain = $this->domainStorage->load($selected_domain_id);
    }
    $form['domain_config_ui']['domain'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain'),
      '#options' => array_merge(['' => $this->t('All Domains')], $this->domainStorage->loadOptionsList()),
      '#default_value' => !empty($selected_domain) ? $selected_domain->id() : '',
      '#ajax' => [
        'callback' => '::switchCallback',
      ],
    ];

    // Add language select field.
    $language_options = ['' => $this->t('Default')];
    foreach ($this->languageManager->getLanguages() as $id => $language) {
      $language_options[$id] = $language->getName();
    }
    $form['domain_config_ui']['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $language_options,
      '#default_value' => $this->domainConfigUiManager->getSelectedLanguageId(),
      '#ajax' => [
        'callback' => '::switchCallback',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form does not require submit handler.
  }

  /**
   * Callback to remember save mode and reload page.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   */
  public static function switchCallback(array &$form, FormStateInterface $form_state) {
    // Extract requesting page URI from ajax URI.
    // Copied from Drupal\Core\Form\FormBuilder::buildFormAction().
    $request = \Drupal::service('request_stack')->getMasterRequest();
    $request_uri = $request->getRequestUri();

    // Prevent cross site requests via the Form API by using an absolute URL
    // when the request uri starts with multiple slashes.
    if (strpos($request_uri, '//') === 0) {
      $request_uri = $request->getUri();
    }

    $parsed = UrlHelper::parse($request_uri);
    unset($parsed['query']['ajax_form'], $parsed['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);

    if (\Drupal::config('domain_config_ui.settings')->get('remember_domain')) {
      // Save domain and language on session.
      $_SESSION['domain_config_ui_domain'] = $form_state->getValue('domain');
      $_SESSION['domain_config_ui_language'] = $form_state->getValue('language');
    }
    else {
      // Pass domain and language as request query parameters.
      $parsed['query']['domain_config_ui_domain'] = $form_state->getValue('domain');
      $parsed['query']['domain_config_ui_language'] = $form_state->getValue('language');
    }

    $request_uri = $parsed['path'] . ($parsed['query'] ? ('?' . UrlHelper::buildQuery($parsed['query'])) : '');

    // Reload the page to get new form values.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($request_uri));
    return $response;
  }

}
