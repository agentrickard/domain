<?php

/**
 * @file
 * Definition of Drupal\domain\DomainInterface.
 */

namespace Drupal\domain;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a domain entity.
 */
interface DomainInterface extends ContentEntityInterface {

  /**
   * Validates the hostname for a domain.
   */
  public function validate();

  /**
   * Tests that a domain responds correctly.
   */
  public function checkResponse();

  /**
   * Detects if the current domain is the active domain.
   */
  public function isActive();

  /**
   * Detects if the current domain is the default domain.
   */
  public function isDefault();

  /**
   * Makes a domain record the default.
   */
  public function saveDefault();

  /**
   * Enables a domain record.
   */
  public function enable();

  /**
   * Disables a domain record.
   */
  public function disable();

  /**
   * Saves a specific domain attribute.
   *
   * @param $key
   *   The property key to save for the $domain object.
   * @param $value
   *   The value to set for the property.
   *
   */
  public function saveAttribute($key, $value);

  /**
   * Sets the base path to this domain.
   */
  public function setPath();

  /**
   * Sets the domain-specific link to the current URL.
   */
  public function setUrl();

  /**
   * Gets the path for a domain.
   */
  public function getPath();

  /**
   * Gets the url for a domain.
   */
  public function getUrl();

}
