<?php

/**
 * @file
 * Definition of Drupal\domain\Entity\Domain.
 */

namespace Drupal\domain\Entity;

use Drupal\domain\DomainInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the domain entity.
 *
 * @ConfigEntityType(
 *   id = "domain",
 *   label = @Translation("Domain record"),
 *   module = "domain",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "render" = "Drupal\domain\DomainRenderController",
 *     "access" = "Drupal\domain\DomainAccessController",
 *     "list_builder" = "Drupal\domain\DomainListController",
 *     "view_builder" = "Drupal\domain\DomainViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\domain\DomainForm",
 *       "edit" = "Drupal\domain\DomainForm",
 *       "delete" = "Drupal\domain\Form\DomainDeleteForm"
 *     }
 *   },
 *   config_prefix = "record",
 *   admin_permission = "administer domains",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "domain_id" = "domain_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "delete-form" = "domain.delete",
 *     "edit-form" = "domain.edit"
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
   * The domain record current url, a calculated value.
   *
   * @var string
   */
  public $url;

  /**
   * The domain record http response test (e.g. 200), a calculated value.
   *
   * @var integer
   */
  public $response;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $default = domain_default_id();
    $domains = domain_load_multiple();
    $values += array(
      'scheme' => empty($GLOBALS['is_https']) ? 'http' : 'https',
      'status' => 1,
      'weight' => count($domains) + 1,
      'is_default' => (int) empty($default),
      // {node_access} still requires a numeric id.
      // @TODO: This is not reliable and creates duplicates.
      'domain_id' => domain_next_id(),
    );
  }

  /**
   * Validates the hostname for a domain.
   */
  public function validate() {
    $hostname = $this->hostname;
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
    $non_ascii = \Drupal::config('domain.settings')->get('allow_non_ascii');
    if (!$non_ascii) {
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
    if (\Drupal::config('domain.settings')->get('www_prefix') && (substr($hostname, 0, strpos($hostname, '.')) == 'www')) {
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
      return t('The domain string is invalid for %subdomain: !errors', array('%subdomain' => $hostname, '!errors' => array('#theme' => 'item_list', '#items' => $error_list)));
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
    try {
      // GuzzleHttp no longer allows for bogus URL calls.
      $request = $this->getHttpClient()->get($url);
    }
    // We cannot know which Guzzle Exception class will be returned; be generic.
    catch (RequestException $e) {
      watchdog_exception('domain', $e);
      // File a general server failure.
      $this->response = 500;
      return;
    }
    // Expected result (i.e. no exception thrown.)
    $this->response = $request->getStatusCode();
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
    return ($this->id() == $domain->id());
  }

  /**
   * Detects if the current domain is the default domain.
   */
  public function isDefault() {
    return (bool) $this->is_default;
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
    $this->path = domain_scheme($this->scheme) . $this->hostname . base_path();
  }

  /**
   * Sets the domain-specific link to the current URL.
   */
  public function setUrl() {
    $this->url = domain_scheme($this->scheme) . $this->hostname . request_uri();
  }

  /**
   * Gets the path for a domain.
   */
  public function getPath() {
    if (!isset($this->path)) {
      $this->setPath();
    }
    return $this->path;
  }

  /**
   * Gets the url for a domain.
   */
  public function getUrl() {
    if (!isset($this->url)) {
      $this->setUrl();
    }
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    // Sets the default domain properly.
    $default = domain_default();
    if (!$default) {
      $this->is_default = 1;
    }
    elseif ($this->is_default && $default->id() != $this->id()) {
      // Swap the current default.
      $default->is_default = 0;
      $default->save();
    }
  }

}
