<?php

/**
 * @file
 * Definition of Drupal\domain\DomainManager.
 */

namespace Drupal\domain;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\ControllerInterface;
use Drupal\domain\DomainManagerInterface;
use Drupal\domain\Plugin\Core\Entity\Domain;

class DomainManager implements DomainManagerInterface {

  public $httpHost;

  public $domain;

  public function __construct() {
    $this->httpHost = NULL;
    $this->domain = NULL;
  }

  public function requestDomain($httpHost) {
    $this->setHttpHost($httpHost);
    $domain = domain_load_hostname($httpHost);
    if (!empty($domain)) {
      $this->setActiveDomain($domain);
    }
  }

  public function setActiveDomain(Domain $domain) {
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
