<?php

/**
 * @file
 * Definition of Drupal\domain\Plugin\Core\Entity\Domain.
 */

namespace Drupal\domain\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the domain entity.
 *
 * @Plugin(
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

}
