<?php

namespace Drupal\domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Unicode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides validation of domain strings against RFC standards for hostnames.
 */
class DomainValidator implements DomainValidatorInterface {

  use StringTranslationTrait;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    // @TODO: Move to a proper service?
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Divide this into separate methods. Do not return Drupal-specific
   * responses.
   */
  public function validate(DomainInterface $domain) {
    $hostname = $domain->getHostname();
    $error_list = array();
    // Check for at least one dot or the use of 'localhost'.
    // Note that localhost can specify a port.
    $localhost_check = explode(':', $hostname);
    if (substr_count($hostname, '.') == 0 && $localhost_check[0] != 'localhost') {
      $error_list[] = $this->t('At least one dot (.) is required, except when using <em>localhost</em>.');
    }
    // Check for one colon only.
    if (substr_count($hostname, ':') > 1) {
      $error_list[] = $this->t('Only one colon (:) is allowed.');
    }
    // If a colon, make sure it is only followed by numbers.
    elseif (substr_count($hostname, ':') == 1) {
      $parts = explode(':', $hostname);
      $port = (int) $parts[1];
      if (strcmp($port, $parts[1])) {
        $error_list[] = $this->t('The port protocol must be an integer.');
      }
    }
    // The domain cannot begin or end with a period.
    if (substr($hostname, 0, 1) == '.') {
      $error_list[] = $this->t('The domain must not begin with a dot (.)');
    }
    // The domain cannot begin or end with a period.
    if (substr($hostname, -1) == '.') {
      $error_list[] = $this->t('The domain must not end with a dot (.)');
    }
    // Check for valid characters, unless using non-ASCII domains.
    $non_ascii = $this->configFactory->get('domain.settings')->get('allow_non_ascii');
    if (!$non_ascii) {
      $pattern = '/^[a-z0-9\.\-:]*$/i';
      if (!preg_match($pattern, $hostname)) {
        $error_list[] = $this->t('Only alphanumeric characters, dashes, and a colon are allowed.');
      }
    }
    // Check for lower case.
    if ($hostname != Unicode::strtolower($hostname)) {
      $error_list[] = $this->t('Only lower-case characters are allowed.');
    }
    // Check for 'www' prefix if redirection / handling is
    // enabled under global domain settings.
    // Note that www prefix handling must be set explicitly in the UI.
    // See http://drupal.org/node/1529316 and http://drupal.org/node/1783042
    if ($this->configFactory->get('domain.settings')->get('www_prefix') && (substr($hostname, 0, strpos($hostname, '.')) == 'www')) {
      $error_list[] = $this->t('WWW prefix handling: Domains must be registered without the www. prefix.');
    }

    // Check existing domains.
    $domains = \Drupal::entityTypeManager()
      ->getStorage('domain')
      ->loadByProperties(array('hostname' => $hostname));
    foreach ($domains as $match) {
      if ($match->id() != $domain->id()) {
        $error_list[] = $this->t('The hostname is already registered.');
      }
    }
    // Allow modules to alter this behavior.
    $this->moduleHandler->alter('domain_validate', $error_list, $hostname);

    // Return the errors, if any.
    if (!empty($error_list)) {
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
   * {@inheritdoc}
   */
  public function checkResponse(DomainInterface $domain) {
    $url = $domain->getPath() . drupal_get_path('module', 'domain') . '/tests/200.png';
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
