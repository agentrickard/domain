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
   * The domain entity access control handler.
   *
   * @var \Drupal\domain\DomainAccessControlHandler
   */
  protected $accessHandler;

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
    // Not loaded directly since it is not an interface.
    $this->accessHandler = $this->entityTypeManager->getAccessControlHandler('domain');
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
   * Determines if a user may access the domain-sensitive form.
   */
  public function canUseDomainConfig() {
    if ($this->currentUser()->hasPermission('administer domains')) {
      $user_domains = 'all';
    }
    else {
      $account = $this->currentUser();
      $user = $this->entityTypeManager->getStorage('user')->load($account->id());
      $user_domains = $this->domainElementManager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
    }
    $permission = $this->currentUser()->hasPermission('use domain config ui') ||
                  $this->currentUser()->hasPermission('administer domain config ui');
    return (!empty($user_domains) && $permission);
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
    // Get the form options.
    $form['domain_config_ui']['domain'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain'),
      '#options' => $this->getDomainOptions(),
      '#default_value' => !empty($selected_domain) ? $selected_domain->id() : '',
      '#ajax' => [
        'callback' => '::switchCallback',
      ],
    ];
    // Add language select field. Domain Config does not rely on core's Config
    // Translation module, so we set our own permission.
    $languages = $this->languageManager->getLanguages();
    if (count($languages) > 1 && $this->currentUser()->hasPermission('translate domain configuration')) {
      $language_options = ['' => $this->t('Default')];
      foreach ($languages as $id => $language) {
        if (!$language->isLocked()) {
          $language_options[$id] = $language->getName();
        }
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
      $form['domain_config_ui']['help'] = [
        '#markup' => $this->t('Changing the domain or language will load its active configuration.'),
      ];
    }
    else {
      $form['domain_config_ui']['help'] = [
        '#markup' => $this->t('Changing the domain will load its active configuration.'),
      ];
    }

    // @TODO: Add cache contexts to form?
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

  /**
   * Gets the available domain list for the form user.
   *
   * @return array
   *   An array of select options.
   */
  public function getDomainOptions() {
    $domains = $this->domainStorage->loadMultipleSorted();
    $options = [];
    foreach ($domains as $domain) {
      // If the user cannot view the domain, then don't show in the list.
      // View here is sufficient, because it means the user is assigned to the
      // domain. We have already checked for the ability to use this form.
      $access = $this->accessHandler->checkAccess($domain, 'view');
      if ($access->isAllowed()) {
        $options[$domain->id()] = $domain->label();
      }
    }
    // The user must have permission to set the default value.
    if ($this->currentUser()->hasPermission('set default domain configuration')) {
      $options = array_merge(['' => $this->t('All Domains')], $options);
    }
    return $options;
  }

}
