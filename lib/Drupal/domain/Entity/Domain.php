<?php

/**
 * @file
 * Definition of Drupal\domain\Entity\Domain.
 */

namespace Drupal\domain\Entity;

use Drupal\domain\DomainInterface;

use Drupal\Core\Entity\ContentEntityBase;
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
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageControllerNG",
 *     "render" = "Drupal\domain\DomainRenderController",
 *     "access" = "Drupal\domain\DomainAccessController",
 *     "form" = {
 *       "default" = "Drupal\domain\DomainFormController",
 *       "edit" = "Drupal\domain\DomainFormController",
 *       "delete" = "Drupal\domain\Form\DomainDeleteForm"
 *     }
 *   },
 *   base_table = "domain",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "domain_id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   view_modes = {
 *     "full" = {
 *       "label" = "Domain record",
 *       "custom_settings" = FALSE
 *     }
 *   },
 *   menu_base_path = "domain/%domain_machine_name",
 *   route_base_path = "admin/structure/domain"
 * )
 */
class Domain extends ContentEntityBase implements DomainInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['domain_id'] = array(
      'label' => t('Domain record ID'),
      'description' => t('The domain record ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The domain record UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['machine_name'] = array(
      'label' => t('Machine name'),
      'description' => t('The domain record machine name.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('The domain record name.'),
      'type' => 'string_field',
    );
    $properties['hostname'] = array(
      'label' => t('Hostname'),
      'description' => t('The domain record hostname.'),
      'type' => 'string_field',
    );
    $properties['status'] = array(
      'label' => t('Status'),
      'description' => t('The domain record status.'),
      'type' => 'boolean_field',
    );
    $properties['weight'] = array(
      'label' => t('Weight'),
      'description' => t('The domain record sort weight.'),
      'type' => 'integer_field',
    );
    $properties['is_default'] = array(
      'label' => t('Default domain'),
      'description' => t('Indicates that the domain record is the default.'),
      'type' => 'boolean_field',
    );
    $properties['scheme'] = array(
      'label' => t('Scheme'),
      'description' => t('The domain record http scheme.'),
      'type' => 'string_field',
    );
    $properties['path'] = array(
      'label' => t('Path'),
      'description' => t('The base URL path for the domain.'),
      'computed' => TRUE,
      'read-only' => FALSE,
      'type' => 'string_field',
    );

    return $properties;
  }

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
    return ($this->machine_name == $domain->machine_name);
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
   * Sets the default domain properly.
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    if (!empty($this->is_default)) {
      // Swap the current default.
      if ($default = domain_default()) {
        $default->is_default = 0;
        $default->save();
      }
    }
  }

}
