<?php
namespace Drupal\domain_config_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SwitchForm extends FormBase {
  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Class constructor.
   */
  public function __construct(DomainNegotiatorInterface $domain_negotiator) {
    // Set the Domain negotiator.
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class with negotiator.
    return new static($container->get('domain.negotiator'));
  }

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

    // Add domain switch select field.
    if ($selected_domain = $this->domainNegotiator->getSelectedDomain()) {
      $selected = $selected_domain->id();
    }
    else {
      $selected = $form_state->getValue('config_save_domain');
    }
    $form['config_save_domain'] = array(
      '#type' => 'select',
      '#title' => 'Save config for:',
      '#options' => array_merge(['' => 'All Domains'], \Drupal::service('domain.loader')->loadOptionsList()),
      '#default_value' => $selected,
      '#ajax' => array(
        'callback' => '::switchCallback',
      ),
    );

    // Attach CSS to position form.
    $form['#attached']['library'][] = 'domain_config_ui/drupal.domain_config_ui.admin';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form does not require submit handler.
  }

  /**
   * Callback to remember save mode.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function switchCallback(array &$form, FormStateInterface $form_state) {
    $this->domainNegotiator->setSelectedDomain($form_state->getValue('config_save_domain'));
    $response = new AjaxResponse();
    return $response;
  }
}
