<?php

namespace Drupal\domain;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a domain entity.
 */
interface DomainInterface extends ConfigEntityInterface {

  /**
   * Detects if the current domain is the active domain.
   *
   * @return bool
   *   TRUE if domain enabled, FALSE otherwise.
   */
  public function isActive();

  /**
   * Detects if the current domain is the default domain.
   *
   * @return bool
   *   TRUE if domain set as default, FALSE otherwise.
   */
  public function isDefault();

  /**
   * Detects if the domain uses https for links.
   *
   * @return bool
   *   TRUE if domain protocol is HTTPS, FALSE otherwise.
   */
  public function isHttps();

  /**
   * Makes a domain record the default.
   */
  public function saveDefault();

  /**
   * Saves a specific domain attribute.
   *
   * @param string $name
   *   The property key to save for the $domain object.
   * @param mixed $value
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
   *   current request is /user the return would be http://example.com/user or
   *   http://one.example.com, depending on the current domain context.
   */
  public function getUrl();

  /**
   * Returns the active scheme for a domain record.
   *
   * This method is to be used when generating URLs.
   *
   * @param bool $add_suffix
   *   Tells the method to return :// after the string.
   *
   * @return string
   *   Returns a valid scheme (http or https), with or without the suffix.
   */
  public function getScheme($add_suffix = TRUE);

  /**
   * Returns the stored scheme value for a domain record.
   *
   * This method is to be used with forms and when saving domain records. It
   * returns the raw value (http|https|variable) of the domain's default scheme.
   *
   * @return string
   *   Returns a stored scheme default (http|https|variable) for the record.
   */
  public function getRawScheme();

  /**
   * Retrieves the value of the response test.
   *
   * @return int
   *   The HTTP response code of the domain test, expected to be 200.
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
   * @param string $name
   *   The name of the property to retrieve.
   * @param mixed $value
   *   The value of the property.
   */
  public function addProperty($name, $value);

  /**
   * Returns a URL object for a domain.
   *
   * @param bool $current_path
   *   Indicates that the link should point to the path of the current request.
   *
   * @return \Drupal\Core\Url
   *   A core URL object.
   */
  public function getLink($current_path = TRUE);

  /**
   * Returns the redirect status of the current domain.
   *
   * @return int|null
   *   If numeric, the type of redirect to issue (301 or 302).
   */
  public function getRedirect();

  /**
   * Sets a redirect on the current domain.
   *
   * @param int $code
   *   A valid HTTP redirect code (301 or 302).
   */
  public function setRedirect($code = 302);

  /**
   * Gets the hostname of the domain.
   *
   * @return string
   *   The domain hostname.
   */
  public function getHostname();

  /**
   * Sets the hostname of the domain.
   *
   * @param string $hostname
   *   The hostname value to set, in the format example.com.
   */
  public function setHostname($hostname);

  /**
   * Gets the numeric id of the domain record.
   *
   * @return int
   *   The domain id.
   */
  public function getDomainId();

  /**
   * Gets the sort weight of the domain record.
   *
   * @return int
   *   The domain record sort weight.
   */
  public function getWeight();

  /**
   * Sets the type of record match returned by the negotiator.
   *
   * @param int $match_type
   *   A numeric constant indicating the type of match derived by the caller.
   *   Use this value to determine if the request needs to be overridden. Valid
   *   types are DomainNegotiator::DOMAIN_MATCH_NONE,
   *   DomainNegotiator::DOMAIN_MATCH_EXACT,
   *   DomainNegotiator::DOMAIN_MATCH_ALIAS.
   */
  public function setMatchType($match_type = DomainNegotiator::DOMAIN_MATCH_EXACT);

  /**
   * Gets the type of record match returned by the negotiator.
   *
   * This value will be set by the domain negotiation routine and is not present
   * when loading a domain record via DomainStorageInterface.
   *
   * @return int
   *   The domain record match type.
   *
   * @see setMatchType()
   */
  public function getMatchType();

  /**
   * Find the port used for the domain.
   *
   * @return string
   *   An optional port string (e.g. ':8080') or an empty string;
   */
  public function getPort();

  /**
   * Creates a unique domain id for this record.
   */
  public function createDomainId();

  /**
   * Retrieves the canonical (registered) hostname for the domain.
   *
   * @return string
   *   A hostname string.
   */
  public function getCanonical();

  /**
   * Sets the canonical (registered) hostname for the domain.
   */
  public function setCanonical($hostname = NULL);

}
