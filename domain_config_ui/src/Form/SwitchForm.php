<?php
namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

class SwitchForm extends FormBase {
  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'domain_config_ui_switch_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only allow access to domain administrators.
    $form['#access'] = $this->currentUser()->hasPermission('administer domains');
    $form = $this->addSwitchFields($form, $form_state);
    return $form;
  }

  /**
   * Helper to add switch fields to form.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function addSwitchFields(array $form, FormStateInterface $form_state) {
    // Create fieldset to group domain fields.
    $form['domain_config_ui'] = [
      '#type' => 'fieldset',
      '#title' => 'Domain Configuration',
      '#weight' => -10,
    ];

    // Add domain switch select field.
    $selected_domain = \Drupal::service('domain_config_ui.manager')->getSelectedDomain();
    $form['domain_config_ui']['config_save_domain'] = [
      '#type' => 'select',
      '#title' => 'Domain',
      '#options' => array_merge(['' => 'All Domains'], \Drupal::service('domain.loader')->loadOptionsList()),
      '#default_value' => $selected_domain ? $selected_domain->id() : '',
      '#ajax' => [
        'callback' => '::switchCallback',
      ],
    ];

    // Add language select field.
    $selected_language = \Drupal::service('domain_config_ui.manager')->getSelectedLanguage();
    $language_options = ['' => 'Default'];
    foreach (\Drupal::languageManager()->getLanguages() as $id => $language) {
      $language_options[$id] = $language->getName();
    }
    $form['domain_config_ui']['config_save_language'] = [
      '#type' => 'select',
      '#title' => 'Language',
      '#options' => $language_options,
      '#default_value' => $selected_language ? $selected_language->getId() : '',
      '#ajax' => [
        'callback' => '::switchCallback',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form does not require submit handler.
  }

  /**
   * Callback to remember save mode and reload page.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function switchCallback(array &$form, FormStateInterface $form_state) {
    // Switch the current domain.
    \Drupal::service('domain_config_ui.manager')->setSelectedDomain($form_state->getValue('config_save_domain'));

    // Switch the current language.
    \Drupal::service('domain_config_ui.manager')->setSelectedLanguage($form_state->getValue('config_save_language'));

    // Extract requesting page URI from ajax URI.
    // Copied from Drupal\Core\Form\FormBuilder::buildFormAction().
    $request_uri = \Drupal::service('request_stack')->getMasterRequest()->getRequestUri();

    // Prevent cross site requests via the Form API by using an absolute URL
    // when the request uri starts with multiple slashes.
    if (strpos($request_uri, '//') === 0) {
      $request_uri = $request->getUri();
    }

    $parsed = UrlHelper::parse($request_uri);
    unset($parsed['query']['ajax_form'], $parsed['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);
    $request_uri = $parsed['path'] . ($parsed['query'] ? ('?' . UrlHelper::buildQuery($parsed['query'])) : '');

    // Reload the page to get new form values.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($request_uri));
    return $response;
  }
}
