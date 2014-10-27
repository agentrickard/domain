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
   *
   * @return boolean
   */
  public function isActive();

  /**
   * Detects if the current domain is the default domain.
   *
   * @return boolean
   */
  public function isDefault();

  /**
   * Detects if the domain uses https for links.
   *
   * @return boolean
   */
  public function isHttps();

  /**
   * Makes a domain record the default.
   */
  public function saveDefault();

  /**
   * Saves a specific domain attribute.
   *
   * @param $name
   *   The property key to save for the $domain object.
   * @param $value
   *   The value to set for the property.
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
   *
   * @return string
   *   A URL string for the base path to the domain. (e.g. http://example.com/)
   */
  public function getPath();

  /**
   * Gets the url for a domain.
   *
   * @return string
   *   A URL string for the current request on the requested domain. If the
   *   current request is http://example.com/user the return would be
   *   http://one.example.com/user.
   */
  public function getUrl();

  /**
   * Returns the scheme for a domain record.
   *
   * @param boolean $add_suffix
   *   Tells the method to return :// after the string.
   *
   * @return string
   *   Returns a valid scheme (http or https), with or without the suffix.
   */
  public function getScheme($add_suffix = TRUE);

  /**
   * Retrieves the value of the response test.
   *
   * @return int
   *   The HTTP response code of the domain test, usually 200.
   */
  public function getResponse();

  /**
   * Sets the value of the response test.
   *
   * @param int $response
   *   The HTTP response code to set.
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
   *
   * @return Drupal\Core\Url
   *   A core URL object.
   */
  public function getLink($current_path = TRUE);

  /**
   * Returns the redirect status of the current domain.
   *
   * @return integer | NULL
   *   If numeric, the type of redirect to issue (301 or 302).
   */
  function getRedirect();

  /**
   * Sets a redirect on the current domain.
   *
   * @param integer $code
   *   A valid HTTP redirect code (301 or 302).
   */
  function setRedirect($code = 302);

  /**
   * Gets the hostname of the domain.
   *
   * @return string
   */
  function getHostname();

  /**
   * Sets the hostname of the domain.
   *
   * @param string $hostname
   *   The hostname value to set, in the format example.com.
   */
  function setHostname($hostname);

  /**
   * Gets the numeric id of the domain record.
   *
   * @return integer
   */
  function getDomainId();

  /**
   * Gets the sort weight of the domain record.
   *
   * @return integer
   */
  function getWeight();

}
