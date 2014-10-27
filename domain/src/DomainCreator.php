<?php

/**
 * @file
 * Definition of Drupal\domain\DomainCreator.
 */

namespace Drupal\domain;

use Drupal\domain\DomainCreatorInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Creates new domain records.
 *
 * This class is a helper that replaces legacy procedural code.
 */
class DomainCreator implements DomainCreatorInterface {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $loader;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainCreator object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * Creates a new domain record object.
   *
   * @param array $values
   *   The values for the domain.
   * @param bool $inherit
   *   Indicates that values should be calculated from the current domain.
   *
   * @return DomainInterface $domain
   *   A domain record object.
   */
  public function createDomain(array $values = array(), $inherit = FALSE) {
    $default = $this->loader->loadDefaultId();
    $domains = $this->loader->loadMultiple();
    $values += array(
      'scheme' => empty($GLOBALS['is_https']) ? 'http' : 'https',
      'status' => 1,
      'weight' => count($domains) + 1,
      'is_default' => (int) empty($default),
    );
    if ($inherit) {
      $values['hostname'] = $this->createHostname();
      $values['name'] = \Drupal::config('system.site')->get('name');
      $values['id'] = $this->createMachineName($values['hostname']);
    }
    // Fix this.
    $domain = entity_create('domain', $values);
    return $domain;
  }

  /**
   * Gets the next numeric id for a domain.
   *
   * Numeric id keys are still used by the node access system.
   *
   * @return integer
   */
  public function createNextId() {
    $domains = $this->loader->loadMultiple();
    $max = 0;
    foreach ($domains as $domain) {
      $domain_id = $domain->getDomainId();
      if ($domain_id > $max) {
        $max = $domain_id;
      }
    }
    return $max + 1;
  }

  /**
   * Gets the hostname of the active request.
   *
   * @return string
   *   The hostname string of the current request.
   */
  public function createHostname() {
    return $this->negotiator->negotiateActiveHostname();
  }

  /**
   * Gets the machine name of a host, used as primary key.
   */
  public function createMachineName($hostname) {
    return preg_replace('/[^a-z0-9_]+/', '_', $hostname);
  }

}
