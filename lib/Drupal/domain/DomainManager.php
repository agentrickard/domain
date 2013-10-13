<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManager.
 */

namespace Drupal\domain;

use Drupal\domain\DomainManagerInterface;
use Drupal\domain\DomainInterface;

class DomainManager implements DomainManagerInterface {

  public $httpHost;

  public $domain;

  public function __construct() {
    $this->httpHost = NULL;
    $this->domain = NULL;
  }

  public function setRequestDomain($httpHost) {
    $this->setHttpHost($httpHost);
    $domain = domain_load_hostname($httpHost);
    if (!empty($domain)) {
      $this->setActiveDomain($domain);
    }
  }

  public function setActiveDomain(DomainInterface $domain) {
    $this->domain = $domain;
  }

  public function getActiveDomain() {
    return $this->domain;
  }

  public function setHttpHost($httpHost) {
    $this->httpHost = $httpHost;
  }

  public function getHttpHost() {
    return $this->httpHost;
  }

}
