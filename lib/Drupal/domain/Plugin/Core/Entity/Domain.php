<?php

/**
 * @file
 * Definition of Drupal\domain\Plugin\Core\Entity\Domain.
 */

namespace Drupal\domain\Plugin\Core\Entity;

use Drupal;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Guzzle\Http\Exception\HttpException;

/**
 * Defines the domain entity.
 *
 * @EntityType(
 *   id = "domain",
 *   label = @Translation("Domain record"),
 *   module = "domain",
 *   controller_class = "Drupal\domain\DomainStorageController",
 *   render_controller_class = "Drupal\domain\DomainRenderController",
 *   form_controller_class = {
 *     "default" = "Drupal\domain\DomainFormController"
 *   },
 *   base_table = "domain",
 *   fieldable = TRUE,
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
 *   menu_base_path = "domain/%domain_machine_name"
 * )
 */
class Domain extends Entity implements ContentEntityInterface {

  /**
   * The domain record id.
   *
   * @var integer
   */
  public $domain_id;

  /**
   * The domain UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * Canonical hostname.
   *
   * @var string
   */
  public $hostname;

  /**
   * Human-readable site name.
   *
   * @var string
   */
  public $name;

  /**
   * Default http scheme.
   *
   * @var string
   */
  public $scheme;

  /**
   * Record status.
   *
   * @var integer
   */
  public $status;

  /**
   * Sort order.
   *
   * @var integer
   */
  public $weight;

  /**
   * Default domain flag.
   *
   * @var integer
   */
  public $is_default;

  /**
   * The domain machine name.
   *
   * @var string
   */
  public $machine_name;

  /**
   * The base URL for a domain. Derived.
   */
  public $path;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->domain_id;
  }

  /**
   * Validates the hostname for a domain.
   */
  public function validate() {
    return 'foo';
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
    return Drupal::httpClient();
  }

  /**
   * Detects if the current domain is the active domain.
   */
  public function isActive() {
    // @TODO: Is this logic sound?
    $active_domain = domain_create(TRUE);
    return ($this->machine_name == $active_domain->machine_name);
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
   * Enables a domain record.
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

}
