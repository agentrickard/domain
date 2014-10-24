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
   * Detects if the domain uses https for links.
   */
  public function isHttps();

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
   * @param $name
   *   The property key to save for the $domain object.
   * @param $value
   *   The value to set for the property.
   *
   */
  public function saveProperty($name, $value);

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
   * Adds a property to the domain record.
   *
   * @param $name
   *  The name of the property to retrieve.
   * @param $value
   *  The value of the property.
   */
  public function addProperty($name, $value);

  /**
   * Returns a URL object for a domain.
   *
   * @param $current_path
   *   Indicates that the link should point to the path of the current request.
   */
  public function getLink($current_path = TRUE);

  function getRedirect();

  function setRedirect($code = 302);

}
