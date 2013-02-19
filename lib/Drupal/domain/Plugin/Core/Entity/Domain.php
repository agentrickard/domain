<?php

/**
 * @file
 * Definition of Drupal\domain\Plugin\Core\Entity\Domain.
 */

namespace Drupal\domain\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Annotation\Plugin;
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
 *     "label" = "name"
 *   },
 *   view_modes = {
 *     "full" = {
 *       "label" = "Domain record",
 *       "custom_settings" = FALSE
 *     }
 *   }
 * )
 */
class Domain extends Entity {

  /**
   * The domain record id.
   *
   * @var integer
   */
  public $domain_id;

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
  public function setDefault() {
    if (!$this->isDefault()) {
      db_update('domain')
        ->fields(array('is_default' => 1))
        ->condition('machine_name', $this->machine_name)
        ->execute();
      db_update('domain')
        ->fields(array('is_default' => 0))
        ->condition('machine_name', $this->machine_name, '<>')
        ->execute();
      $this->is_default = 1;
    }
    else {
      drupal_set_message(t('The selected domain is already the default.'), 'warning');
    }
  }

  /**
   * Enables a domain record.
   */
  public function enable() {
    db_update('domain')
      ->fields(array('status' => 1))
      ->condition('machine_name', $this->machine_name)
      ->execute();
    $this->status = 1;
  }

  /**
   * Enables a domain record.
   */
  public function disable() {
    if (!$this->isDefault()) {
      db_update('domain')
        ->fields(array('status' => 0))
        ->condition('machine_name', $this->machine_name)
        ->execute();
      $this->status = 0;
    }
    else {
      drupal_set_message(t('The default domain cannot be disabled.'), 'warning');
    }
  }

}
