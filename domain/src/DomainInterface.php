<?php

/**
 * @file
 * Definition of Drupal\domain\DomainInterface.
 */

namespace Drupal\domain;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a domain entity.
 */
interface DomainInterface extends ConfigEntityInterface {

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
  public function saveProperty($key, $value);

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

  /**
   * Returns the scheme for a domain record.
   */
  public function getScheme($add_suffix = TRUE);

  /**
   * Retrieves the value of the response test.
   */
  public function getResponse();

  /**
   * Sets the value of the response test.
   */
  public function setResponse($response);

  /**
   * Gets a property from the domain record.
   *
   * @param $name
   *  The name of the property to retrieve.
   */
  public function getProperty($name);

}
