<?php

namespace Drupal\domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Unicode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides validation of domain strings against RFC standards for hostnames.
 */
class DomainValidator implements DomainValidatorInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \GuzzleHttp\Client $httpClient
   *   The HTTP client.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, Client $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('domain.settings');
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Verify division into separate methods.
   * @TODO: Do not return Drupal-specific responses.
   */
  public function validate(DomainInterface $domain) {
    $hostname = $domain->getHostname();

    // First, do generalized hostname validation.
    $error_list = $this->staticHostnameValidation($hostname);

    // Check for valid characters, unless using non-ASCII domains.
    if (! $this->config->get('allow_non_ascii') && ! preg_match('/^[a-z0-9:.-]*$/i', $hostname)) {
      $error_list[] = $this->t('Only alphanumeric characters, dashes, and a colon are allowed.');
    }

    // Check for 'www' prefix if redirection / handling is
    // enabled under global domain settings.
    // Note that www prefix handling must be set explicitly in the UI.
    // See http://drupal.org/node/1529316 and http://drupal.org/node/1783042
    if ($this->config->get('www_prefix') && 0 === strpos($hostname, '.www')) {
      $error_list[] = $this->t('WWW prefix handling: Domains must be registered without the www. prefix.');
    }

    // Check existing domains.
    try {
      $domains = $this->entityTypeManager->getStorage('domain')
        ->loadByProperties(array('hostname' => $hostname));
      foreach ($domains as $match) {
        if ($match->id() !== $domain->id()) {
          $error_list[] = $this->t('The hostname is already registered.');
        }
      }
    }
    catch (\Exception $e) {
      $error_list[] = $this->t('Could not open domain storage');
    }

    // Allow modules to alter this behavior.
    $this->moduleHandler->invokeAll('domain_validate', array($error_list, $hostname));

    // Return the errors, if any.
    if (count($error_list)) {
      return $this->t('The domain string is invalid for %subdomain: @errors', array(
        '%subdomain' => $hostname,
        '@errors' => array(
          '#theme' => 'item_list',
          '#items' => $error_list,
        ),
      ));
    }

    return array();
  }

  /**
   * Perform general hostname validation.
   *
   * None of the checks in this methed are Drupal-specific or are affected by
   * modules settings. Therefore, it might be possible to replace with a
   * general-purpose external hostname validation process.
   *
   * @param string $hostname
   *   The host name.
   * @return array
   *   Any errors detected by static inspection.
   */
  private function staticHostnameValidation($hostname) {
    $errors = array();

    $parts = explode(':', $hostname);

    // Check for at least one dot or the use of 'localhost'.
    // Note that localhost can specify a port.
    if ($parts[0] !== 'localhost' && substr_count($hostname, '.') === 0) {
      $errors[] = $this->t('At least one dot (.) is required, except when using <em>localhost</em>.');
    }

    // Check for one colon only.
    if (count($parts) > 2) {
      $errors[] = $this->t('Only one colon (:) is allowed.');
    }
    // If a colon, make sure it is only followed by numbers.
    elseif (count($parts) === 2) {
      $port = (int) $parts[1];
      if (strcmp($port, $parts[1])) {
        $errors[] = $this->t('The port protocol must be an integer.');
      }
    }

    // The domain cannot begin or end with a period.
    if (0 === strpos($hostname, '.')) {
      $errors[] = $this->t('The domain must not begin with a dot (.)');
    }

    // The domain cannot begin or end with a period.
    if (substr($hostname, -1) === '.') {
      $errors[] = $this->t('The domain must not end with a dot (.)');
    }

    // Check for lower case.
    if ($hostname !== Unicode::strtolower($hostname)) {
      $errors[] = $this->t('Only lower-case characters are allowed.');
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function checkResponse(DomainInterface $domain, $test_path = '') {
    $url = $domain->getPath() .
      ($test_path ?: drupal_get_path('module', 'domain') . '/tests/200.png');
    try {
      // GuzzleHttp no longer allows for bogus URL calls.
      $request = $this->httpClient->get($url);
    }
    // We cannot know which Guzzle Exception class will be returned; be generic.
    catch (RequestException $e) {
      watchdog_exception('domain', $e);
      // File a general server failure.
      $domain->setResponse(500);
      return;
    }
    // Expected result (i.e. no exception thrown.)
    $domain->setResponse($request->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields() {
    return array('hostname', 'name', 'id', 'scheme', 'status', 'weight');
  }

}
