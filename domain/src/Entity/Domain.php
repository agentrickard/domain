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
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "view_builder" = "Drupal\domain\DomainViewBuilder",
 *     "access" = "Drupal\domain\DomainAccessControlHandler",
 *     "list_builder" = "Drupal\domain\DomainListBuilder",
 *     "view_builder" = "Drupal\domain\DomainViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\domain\DomainForm",
 *       "edit" = "Drupal\domain\DomainForm",
 *       "delete" = "Drupal\domain\Form\DomainDeleteForm"
 *     }
 *   },
 *   config_prefix = "record",
 *   admin_permission = "administer domains",
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
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $resolver = \Drupal::service('domain.resolver');
    $default = $resolver->getDefaultId();
    $domains = $resolver->loadMultiple();
    $values += array(
      'scheme' => empty($GLOBALS['is_https']) ? 'http' : 'https',
      'status' => 1,
      'weight' => count($domains) + 1,
      'is_default' => (int) empty($default),
      // {node_access} still requires a numeric id.
      // @TODO: This is not reliable and creates duplicates.
      'domain_id' => $resolver->getNextId(),
    );
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
    $resolver = \Drupal::service('domain.resolver');
    $domain = $resolver->getActiveDommain();
    if (empty($domain)) {
      return FALSE;
    }
    return ($this->id() == $domain->id());
  }

  /**
   * Detects if the domain is the default domain.
   */
  public function isDefault() {
    return (bool) $this->is_default;
  }

  /**
   * Detects if the domain is enabled.
   */
  public function isEnabled() {
    return (bool) $this->status;
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
    $this->path = $this->getScheme() . $this->hostname . base_path();
  }

  /**
   * Sets the domain-specific link to the current URL.
   */
  public function setUrl() {
    $this->url = $this->getScheme() . $this->hostname . request_uri();
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
    $resolver = \Drupal::service('domain.resolver');
    $default = $resolver->getDefaultDomain();
    if (!$default) {
      $this->is_default = 1;
    }
    elseif ($this->is_default && $default->id() != $this->id()) {
      // Swap the current default.
      $default->is_default = 0;
      $default->save();
    }
  }

  /**
   * Returns the scheme for a domain record.
   */
  public function getScheme() {
    $scheme = $domain->scheme;
    if ($scheme != 'https') {
      $scheme = 'http';
    }
    $scheme .= ($add_suffix) ? '://' : '';

    return $scheme;
  }

  public function getResponse() {
    if (empty($this->response)) {
      $validator = \Drupal::service('domain.validator');
      $validator->checkResponse($this);
    }
  }

  public function setResponse($response) {
    $this->response = $response;
  }


}
