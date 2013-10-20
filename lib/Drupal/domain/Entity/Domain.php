<?php

/**
 * @file
 * Definition of Drupal\domain\Entity\Domain.
 */

namespace Drupal\domain\Entity;

use Drupal\domain\DomainInterface;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Guzzle\Http\Exception\HttpException;

/**
 * Defines the domain entity.
 *
 * @EntityType(
 *   id = "domain",
 *   label = @Translation("Domain record"),
 *   module = "domain",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "render" = "Drupal\domain\DomainRenderController",
 *     "access" = "Drupal\domain\DomainAccessController",
 *     "list" = "Drupal\domain\DomainListController",
 *     "form" = {
 *       "default" = "Drupal\domain\DomainFormController",
 *       "edit" = "Drupal\domain\DomainFormController",
 *       "delete" = "Drupal\domain\DomainFormController"
 *     }
 *   },
 *   config_prefix = "domain.domain",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "domain_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   }
 * )
 */
class Domain extends ConfigEntityBase implements DomainInterface {

  /**
   * The ID of the domain entity.
   *
   * @var string
   */
  public $id;

  /**
   * The domain record ID.
   *
   * @var integer
   */
  public $domain_id;

  /**
   * The domain record UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The domain record machine_name.
   *
   * @var string
   */
  public $machine_name;

  /**
   * The domain list name (e.g. Drupal).
   *
   * @var string
   */
  public $name;

  /**
   * The domain hostname (e.g. example.com).
   *
   * @var string
   */
  public $hostname;

  /**
   * The domain status.
   *
   * @var boolean
   */
  public $status;

  /**
   * The domain record sort order.
   *
   * @var integer
   */
  public $weight;

  /**
   * Indicates the default domain.
   *
   * @var boolean
   */
  public $is_default;

  /**
   * The domain record protocol (e.g. http://).
   *
   * @var string
   */
  public $scheme;

  /**
   * The domain record base path, a calculated value.
   *
   * @var string
   */
  public $path;

  /**
   * The domain record base url, a calculated value.
   *
   * @var string
   */
  public $url;

  /**
   * The domain recordd http response test (e.g. 200), a calculated value.
   *
   * @var integer
   */
  public $response;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('domain_id')->value;
  }

  /**
   * Validates the hostname for a domain.
   */
  public function validate() {
    $hostname = $this->hostname->value;
    $error_list = array();
    // Check for at least one dot or the use of 'localhost'.
    // Note that localhost can specify a port.
    $localhost_check = explode(':', $hostname);
    if (substr_count($hostname, '.') == 0 && $localhost_check[0] != 'localhost') {
      $error_list[] = t('At least one dot (.) is required, except when using <em>localhost</em>.');
    }
    // Check for one colon only.
    if (substr_count($hostname, ':') > 1) {
      $error_list[] = t('Only one colon (:) is allowed.');
    }
    // If a colon, make sure it is only followed by numbers.
    elseif (substr_count($hostname, ':') == 1) {
      $parts = explode(':', $hostname);
      $port = (int) $parts[1];
      if (strcmp($port, $parts[1])) {
        $error_list[] = t('The port protocol must be an integer.');
      }
    }
    // The domain cannot begin or end with a period.
    if (substr($hostname, 0, 1) == '.') {
      $error_list[] = t('The domain must not begin with a dot (.)');
    }
    // The domain cannot begin or end with a period.
    if (substr($hostname, -1) == '.') {
      $error_list[] = t('The domain must not end with a dot (.)');
    }
    // Check for valid characters, unless using non-ASCII domains.
    if (!variable_get('domain_allow_non_ascii', FALSE)) {
      $pattern = '/^[a-z0-9\.\-:]*$/i';
      if (!preg_match($pattern, $hostname)) {
        $error_list[] = t('Only alphanumeric characters, dashes, and a colon are allowed.');
      }
    }
    // Check for lower case.
    if ($hostname != drupal_strtolower($hostname)) {
      $error_list[] = t('Only lower-case characters are allowed.');
    }
    // Check for 'www' prefix if redirection / handling is enabled under global domain settings.
    // Note that www prefix handling must be set explicitly in the UI.
    // See http://drupal.org/node/1529316 and http://drupal.org/node/1783042
    if (variable_get('domain_www', 0) && (substr($hostname, 0, strpos($hostname, '.')) == 'www')) {
      $error_list[] = t('WWW prefix handling: Domains must be registered without the www. prefix.');
    }

    // Check existing domains.
    $domains = entity_load_multiple_by_properties('domain', array('hostname' => $hostname));
    foreach ($domains as $domain) {
      if ($domain->id() != $this->id()) {
        $error_list[] = t('The hostname is already registered.');
      }
    }
    // Allow modules to alter this behavior.
    \Drupal::moduleHandler()->invokeAll('domain_validate', $error_list, $hostname);

    // Return the errors, if any.
    if (!empty($error_list)) {
      return t('The domain string is invalid for %subdomain:', array('%subdomain' => $hostname)) . theme('item_list', array('items' => $error_list));
    }

    return array();
  }

  /**
   * Tests that a domain responds correctly.
   *
   * This is a server-level configuration test. The requested image should be
   * returned properly.
   */
  public function checkResponse() {
    $url = $this->getPath() . drupal_get_path('module', 'domain') . '/tests/200.png';
    $request = $this->getHttpClient()->get($url);
    try {
      $response = $request->send();
      // Expected result.
      $this->response = $response->getStatusCode();
    }
    // We cannot know which Guzzle Exception class will be returned; be generic.
    catch (HttpException $e) {
      watchdog_exception('domain', $e);
      // File a general server failure.
      $this->response = 500;
    }
  }

  /**
   * Sets the HTTP Client dependency.
   *
   * @TODO: Move to a proper service?
   */
  protected function getHttpClient() {
    return \Drupal::httpClient();
  }

  /**
   * Detects if the current domain is the active domain.
   */
  public function isActive() {
    $domain = domain_get_domain();
    if (empty($domain)) {
      // @TODO: set the default domain in the manager?
      return FALSE;
    }
    return ($this->machine_name->value == $domain->machine_name->value);
  }

  /**
   * Detects if the current domain is the default domain.
   */
  public function isDefault() {
    return (bool) $this->is_default->value;
  }

  /**
   * Makes a domain record the default.
   */
  public function saveDefault() {
    if (!$this->isDefault()) {
      // Swap the current default.
      if ($default = domain_default()) {
        $default->is_default = 0;
        $default->save();
      }
      // Save the new default.
      $this->is_default = 1;
      $this->save();
    }
    else {
      drupal_set_message(t('The selected domain is already the default.'), 'warning');
    }
  }

  /**
   * Enables a domain record.
   */
  public function enable() {
    $this->status = 1;
    $this->save();
  }

  /**
   * Disables a domain record.
   */
  public function disable() {
    if (!$this->isDefault()) {
      $this->status = 0;
      $this->save();
    }
    else {
      drupal_set_message(t('The default domain cannot be disabled.'), 'warning');
    }
  }

  /**
   * Saves a specific domain attribute.
   */
  public function saveAttribute($key, $value) {
    if (isset($this->{$key})) {
      $this->{$key} = $value;
      $this->save();
      drupal_set_message(t('The @key attribute was set to @value for domain @hostname.', array('@key' => $key, '@value' => $value, '@hostname' => $this->hostname)));
    }
    else {
      drupal_set_message(t('The @key attribute does not exist.', array('@key' => $key)));
    }
  }

  /**
   * Sets the base path to this domain.
   */
  public function setPath() {
    $this->path = domain_scheme($this->scheme->value) . $this->hostname->value . base_path();
  }

  /**
   * Sets the domain-specific link to the current URL.
   */
  public function setUrl() {
    $this->url = domain_scheme($this->scheme->value) . $this->hostname->value . request_uri();
  }

  /**
   * Gets the path for a domain.
   */
  public function getPath() {
    if (!isset($this->path)) {
      $this->setPath();
    }
    return $this->path->value;
  }

  /**
   * Gets the url for a domain.
   */
  public function getUrl() {
    if (!isset($this->url)) {
      $this->setUrl();
    }
    return $this->url->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // Sets the default domain properly.
    $default = domain_default();
    if (!$default) {
      $this->is_default = 1;
    }
    elseif ($this->is_default->value && $default->id() != $this->id()) {
      // Swap the current default.
      $default->is_default = 0;
      $default->save();
    }
  }

}
